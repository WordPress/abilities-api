<?php declare( strict_types = 1 );

/**
 * REST API: WP_REST_Abilities_List_Controller class
 *
 * @package WordPress
 * @subpackage REST_API
 * @since 0.1.0
 */

/**
 * Core controller used to access abilities via the REST API.
 *
 * @since 0.1.0
 *
 * @see WP_REST_Controller
 */
class WP_REST_Abilities_List_Controller extends WP_REST_Controller {

	/**
	 * REST API namespace.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $namespace = 'wp/v2';

	/**
	 * REST API base route.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	protected $rest_base = 'abilities';

	/**
	 * Registers the routes for abilities.
	 *
	 * @since 0.1.0
	 *
	 * @see register_rest_route()
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[a-zA-Z0-9\-\/]+)',
			array(
				'args' => array(
					'id' => array(
						'description' => __( 'Unique identifier for the ability.' ),
						'type'        => 'string',
						'pattern'     => '^[a-zA-Z0-9\-\/]+$',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	/**
	 * Retrieves all abilities.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$abilities = wp_get_abilities();

		// Handle pagination.
		$page     = $request['page'];
		$per_page = $request['per_page'];
		$offset   = ( $page - 1 ) * $per_page;

		$total_abilities = count( $abilities );
		$max_pages       = ceil( $total_abilities / $per_page );

		$abilities = array_slice( $abilities, $offset, $per_page );

		$data = array();
		foreach ( $abilities as $ability ) {
			$item   = $this->prepare_item_for_response( $ability, $request );
			$data[] = $this->prepare_response_for_collection( $item );
		}

		$response = rest_ensure_response( $data );

		$response->header( 'X-WP-Total', $total_abilities );
		$response->header( 'X-WP-TotalPages', $max_pages );

		$request_params = $request->get_query_params();
		$base = add_query_arg( urlencode_deep( $request_params ), rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $page < $max_pages ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Retrieves a specific ability.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		$ability = wp_get_ability( $request['id'] );

		if ( ! $ability ) {
			return new WP_Error(
				'rest_ability_not_found',
				__( 'Ability not found.' ),
				array( 'status' => 404 )
			);
		}

		$data = $this->prepare_item_for_response( $ability, $request );
		return rest_ensure_response( $data );
	}

	/**
	 * Checks if a given request has access to read abilities.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		return current_user_can( 'read' );
	}

	/**
	 * Prepares an ability for response.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Ability      $ability The ability object.
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response Response object.
	 */
	public function prepare_item_for_response( $ability, $request ) {
		$data = array(
			'id'            => $ability->get_name(),
			'label'         => $ability->get_label(),
			'description'   => $ability->get_description(),
			'input_schema'  => $ability->get_input_schema(),
			'output_schema' => $ability->get_output_schema(),
			'meta'          => $ability->get_meta(),
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data = $this->add_additional_fields_to_object( $data, $request );
		$data = $this->filter_response_by_context( $data, $context );

		$response = rest_ensure_response( $data );

		$links = array(
			'self' => array(
				'href' => rest_url( sprintf( '%s/%s/%s', $this->namespace, $this->rest_base, $ability->get_name() ) ),
			),
			'collection' => array(
				'href' => rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ),
			),
		);

		// Add run link for all abilities
		$links['run'] = array(
			'href' => rest_url( sprintf( '%s/%s/%s/run', $this->namespace, $this->rest_base, $ability->get_name() ) ),
		);

		$response->add_links( $links );

		return $response;
	}

	/**
	 * Retrieves the ability's schema, conforming to JSON Schema.
	 *
	 * @since 0.1.0
	 *
	 * @return array Item schema data.
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'ability',
			'type'       => 'object',
			'properties' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the ability.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'label' => array(
					'description' => __( 'Display label for the ability.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'description' => array(
					'description' => __( 'Description of the ability.' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'input_schema' => array(
					'description' => __( 'JSON Schema for the ability input.' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'output_schema' => array(
					'description' => __( 'JSON Schema for the ability output.' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'meta' => array(
					'description' => __( 'Meta information about the ability.' ),
					'type'        => 'object',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	/**
	 * Retrieves the query params for collections.
	 *
	 * @since 0.1.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			'page' => array(
				'description'       => __( 'Current page of the collection.' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
				'minimum'           => 1,
			),
			'per_page' => array(
				'description'       => __( 'Maximum number of items to be returned in result set.' ),
				'type'              => 'integer',
				'default'           => 50,
				'minimum'           => 1,
				'maximum'           => 100,
				'sanitize_callback' => 'absint',
				'validate_callback' => 'rest_validate_request_arg',
			),
		);
	}
}
