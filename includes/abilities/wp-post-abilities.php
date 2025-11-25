<?php
/**
 * Post CRUD Abilities registration.
 *
 * Provides standardized Create, Read, Update, and Delete abilities
 * for all WordPress post types.
 *
 * @package WordPress
 * @subpackage Abilities_API
 * @since 6.9.0
 */

declare( strict_types = 1 );

/**
 * Registers the post ability category.
 *
 * @since 6.9.0
 *
 * @return void
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function wp_register_post_ability_categories(): void {
	wp_register_ability_category(
		'content',
		array(
			'label'       => __( 'Content' ),
			'description' => __( 'Abilities for creating, reading, updating, and deleting posts of any type.' ),
		)
	);
}

/**
 * Registers the post CRUD abilities.
 *
 * @since 6.9.0
 *
 * @return void
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function wp_register_post_abilities(): void {
	$category = 'content';

	// =========================================================================
	// core/get-post - Read a single post by ID
	// =========================================================================
	wp_register_ability(
		'core/get-post',
		array(
			'label'               => __( 'Get Post' ),
			'description'         => __( 'Retrieves a single post by ID. Optionally validates the post type.' ),
			'category'            => $category,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => array(
						'type'        => 'integer',
						'description' => __( 'The post ID to retrieve.' ),
					),
					'post_type' => array(
						'type'        => 'string',
						'description' => __( 'Optional: Validate that the post is of this type.' ),
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => array(
						'type'        => 'integer',
						'description' => __( 'The post ID.' ),
					),
					'title'     => array(
						'type'        => 'string',
						'description' => __( 'The post title.' ),
					),
					'content'   => array(
						'type'        => 'string',
						'description' => __( 'The post content.' ),
					),
					'excerpt'   => array(
						'type'        => 'string',
						'description' => __( 'The post excerpt.' ),
					),
					'status'    => array(
						'type'        => 'string',
						'description' => __( 'The post status.' ),
					),
					'post_type' => array(
						'type'        => 'string',
						'description' => __( 'The post type.' ),
					),
					'date'      => array(
						'type'        => 'string',
						'description' => __( 'The post creation date.' ),
					),
					'modified'  => array(
						'type'        => 'string',
						'description' => __( 'The post modification date.' ),
					),
					'author'    => array(
						'type'        => 'integer',
						'description' => __( 'The post author ID.' ),
					),
					'link'      => array(
						'type'        => 'string',
						'description' => __( 'The post permalink.' ),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) {
				$post_id = absint( $input['id'] );
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return new WP_Error(
						'post_not_found',
						__( 'Post not found.' ),
						array( 'status' => 404 )
					);
				}

				// Validate post_type if specified.
				if ( ! empty( $input['post_type'] ) && $post->post_type !== $input['post_type'] ) {
					return new WP_Error(
						'invalid_post_type',
						__( 'Post is not of the specified type.' ),
						array( 'status' => 400 )
					);
				}

				return array(
					'id'        => $post->ID,
					'title'     => $post->post_title,
					'content'   => $post->post_content,
					'excerpt'   => $post->post_excerpt,
					'status'    => $post->post_status,
					'post_type' => $post->post_type,
					'date'      => $post->post_date,
					'modified'  => $post->post_modified,
					'author'    => (int) $post->post_author,
					'link'      => get_permalink( $post->ID ),
				);
			},
			'permission_callback' => static function ( $input = array() ): bool {
				if ( empty( $input['id'] ) ) {
					return false;
				}
				$post = get_post( absint( $input['id'] ) );
				if ( ! $post ) {
					return false;
				}
				return current_user_can( 'read_post', $post->ID );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	// =========================================================================
	// core/list-posts - List posts with filtering and pagination
	// =========================================================================
	wp_register_ability(
		'core/list-posts',
		array(
			'label'               => __( 'List Posts' ),
			'description'         => __( 'Retrieves a paginated list of posts with optional filtering by type, status, and search term.' ),
			'category'            => $category,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'post_type' => array(
						'type'        => 'string',
						'description' => __( 'The post type to query.' ),
						'default'     => 'post',
					),
					'status'    => array(
						'type'        => 'string',
						'description' => __( 'The post status to filter by.' ),
						'default'     => 'publish',
					),
					'per_page'  => array(
						'type'        => 'integer',
						'description' => __( 'Number of posts per page.' ),
						'default'     => 10,
						'minimum'     => 1,
						'maximum'     => 100,
					),
					'page'      => array(
						'type'        => 'integer',
						'description' => __( 'Page number for pagination.' ),
						'default'     => 1,
						'minimum'     => 1,
					),
					'search'    => array(
						'type'        => 'string',
						'description' => __( 'Search term to filter posts.' ),
					),
					'orderby'   => array(
						'type'        => 'string',
						'description' => __( 'Field to order by.' ),
						'default'     => 'date',
						'enum'        => array( 'date', 'title', 'modified', 'ID' ),
					),
					'order'     => array(
						'type'        => 'string',
						'description' => __( 'Order direction.' ),
						'default'     => 'DESC',
						'enum'        => array( 'ASC', 'DESC' ),
					),
				),
				'additionalProperties' => false,
				'default'              => array(),
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'posts'       => array(
						'type'        => 'array',
						'description' => __( 'Array of post objects.' ),
						'items'       => array(
							'type'       => 'object',
							'properties' => array(
								'id'        => array( 'type' => 'integer' ),
								'title'     => array( 'type' => 'string' ),
								'excerpt'   => array( 'type' => 'string' ),
								'status'    => array( 'type' => 'string' ),
								'post_type' => array( 'type' => 'string' ),
								'date'      => array( 'type' => 'string' ),
								'link'      => array( 'type' => 'string' ),
							),
						),
					),
					'total'       => array(
						'type'        => 'integer',
						'description' => __( 'Total number of posts matching the query.' ),
					),
					'total_pages' => array(
						'type'        => 'integer',
						'description' => __( 'Total number of pages.' ),
					),
				),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ): array {
				$input = is_array( $input ) ? $input : array();

				$post_type = sanitize_key( $input['post_type'] ?? 'post' );
				$status    = sanitize_key( $input['status'] ?? 'publish' );
				$per_page  = absint( $input['per_page'] ?? 10 );
				$page      = absint( $input['page'] ?? 1 );
				$orderby   = sanitize_key( $input['orderby'] ?? 'date' );
				$order     = strtoupper( sanitize_key( $input['order'] ?? 'DESC' ) );

				// Clamp per_page.
				$per_page = max( 1, min( 100, $per_page ) );
				$page     = max( 1, $page );

				$query_args = array(
					'post_type'      => $post_type,
					'post_status'    => $status,
					'posts_per_page' => $per_page,
					'paged'          => $page,
					'orderby'        => $orderby,
					'order'          => $order,
				);

				if ( ! empty( $input['search'] ) ) {
					$query_args['s'] = sanitize_text_field( $input['search'] );
				}

				$query = new WP_Query( $query_args );
				$posts = array();

				/** @var WP_Post $post */
				foreach ( $query->posts as $post ) {
					$posts[] = array(
						'id'        => $post->ID,
						'title'     => $post->post_title,
						'excerpt'   => $post->post_excerpt,
						'status'    => $post->post_status,
						'post_type' => $post->post_type,
						'date'      => $post->post_date,
						'link'      => get_permalink( $post->ID ),
					);
				}

				return array(
					'posts'       => $posts,
					'total'       => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
				);
			},
			'permission_callback' => static function ( $input = array() ): bool {
				$post_type     = sanitize_key( $input['post_type'] ?? 'post' );
				$post_type_obj = get_post_type_object( $post_type );

				if ( ! $post_type_obj ) {
					return false;
				}

				return current_user_can( $post_type_obj->cap->read );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => true,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	// =========================================================================
	// core/create-post - Create a new post
	// =========================================================================
	wp_register_ability(
		'core/create-post',
		array(
			'label'               => __( 'Create Post' ),
			'description'         => __( 'Creates a new post of any type with the specified title and content.' ),
			'category'            => $category,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'title'     => array(
						'type'        => 'string',
						'description' => __( 'The post title.' ),
					),
					'content'   => array(
						'type'        => 'string',
						'description' => __( 'The post content (can include block markup).' ),
						'default'     => '',
					),
					'excerpt'   => array(
						'type'        => 'string',
						'description' => __( 'The post excerpt.' ),
						'default'     => '',
					),
					'status'    => array(
						'type'        => 'string',
						'description' => __( 'The post status.' ),
						'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
						'default'     => 'draft',
					),
					'post_type' => array(
						'type'        => 'string',
						'description' => __( 'The post type.' ),
						'default'     => 'post',
					),
				),
				'required'             => array( 'title' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the created post.' ),
					),
					'link'      => array(
						'type'        => 'string',
						'description' => __( 'The permalink to the post.' ),
					),
					'edit_link' => array(
						'type'        => 'string',
						'description' => __( 'The URL to edit the post.' ),
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) {
				$post_type = sanitize_key( $input['post_type'] ?? 'post' );

				// Validate post type exists.
				if ( ! post_type_exists( $post_type ) ) {
					return new WP_Error(
						'invalid_post_type',
						__( 'Invalid post type.' ),
						array( 'status' => 400 )
					);
				}

				$post_data = array(
					'post_title'   => sanitize_text_field( $input['title'] ),
					'post_content' => wp_kses_post( $input['content'] ?? '' ),
					'post_excerpt' => sanitize_textarea_field( $input['excerpt'] ?? '' ),
					'post_status'  => sanitize_key( $input['status'] ?? 'draft' ),
					'post_type'    => $post_type,
					'post_author'  => get_current_user_id(),
				);

				$post_id = wp_insert_post( $post_data, true );

				if ( is_wp_error( $post_id ) ) {
					return $post_id;
				}

				return array(
					'id'        => $post_id,
					'link'      => get_permalink( $post_id ),
					'edit_link' => get_edit_post_link( $post_id, 'raw' ),
				);
			},
			'permission_callback' => static function ( $input = array() ): bool {
				$post_type     = sanitize_key( $input['post_type'] ?? 'post' );
				$post_type_obj = get_post_type_object( $post_type );

				if ( ! $post_type_obj ) {
					return false;
				}

				// Check create capability.
				if ( ! current_user_can( $post_type_obj->cap->create_posts ) ) {
					return false;
				}

				// Check publish capability if status is publish.
				$status = sanitize_key( $input['status'] ?? 'draft' );
				return 'publish' !== $status || current_user_can( $post_type_obj->cap->publish_posts );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => false,
				),
				'show_in_rest' => true,
			),
		)
	);

	// =========================================================================
	// core/update-post - Update an existing post
	// =========================================================================
	wp_register_ability(
		'core/update-post',
		array(
			'label'               => __( 'Update Post' ),
			'description'         => __( 'Updates an existing post. Only provided fields will be modified.' ),
			'category'            => $category,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'      => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the post to update.' ),
					),
					'title'   => array(
						'type'        => 'string',
						'description' => __( 'The new post title.' ),
					),
					'content' => array(
						'type'        => 'string',
						'description' => __( 'The new post content.' ),
					),
					'excerpt' => array(
						'type'        => 'string',
						'description' => __( 'The new post excerpt.' ),
					),
					'status'  => array(
						'type'        => 'string',
						'description' => __( 'The new post status.' ),
						'enum'        => array( 'draft', 'publish', 'pending', 'private' ),
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'        => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the updated post.' ),
					),
					'modified'  => array(
						'type'        => 'string',
						'description' => __( 'The modification timestamp.' ),
					),
					'link'      => array(
						'type'        => 'string',
						'description' => __( 'The permalink to the post.' ),
					),
					'edit_link' => array(
						'type'        => 'string',
						'description' => __( 'The URL to edit the post.' ),
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) {
				$post_id = absint( $input['id'] );
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return new WP_Error(
						'post_not_found',
						__( 'Post not found.' ),
						array( 'status' => 404 )
					);
				}

				// Build update array with only provided fields.
				$post_data = array( 'ID' => $post_id );

				if ( isset( $input['title'] ) ) {
					$post_data['post_title'] = sanitize_text_field( $input['title'] );
				}
				if ( isset( $input['content'] ) ) {
					$post_data['post_content'] = wp_kses_post( $input['content'] );
				}
				if ( isset( $input['excerpt'] ) ) {
					$post_data['post_excerpt'] = sanitize_textarea_field( $input['excerpt'] );
				}
				if ( isset( $input['status'] ) ) {
					$post_data['post_status'] = sanitize_key( $input['status'] );
				}

				$result = wp_update_post( $post_data, true );

				if ( is_wp_error( $result ) ) {
					return $result;
				}

				$updated_post = get_post( $post_id );

				return array(
					'id'        => $post_id,
					'modified'  => $updated_post ? $updated_post->post_modified : '',
					'link'      => get_permalink( $post_id ),
					'edit_link' => get_edit_post_link( $post_id, 'raw' ),
				);
			},
			'permission_callback' => static function ( $input = array() ): bool {
				if ( empty( $input['id'] ) ) {
					return false;
				}

				$post_id = absint( $input['id'] );
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return false;
				}

				// Check edit capability for this specific post.
				if ( ! current_user_can( 'edit_post', $post_id ) ) {
					return false;
				}

				// Check publish capability if changing to publish.
				if ( isset( $input['status'] ) && 'publish' === $input['status'] && 'publish' !== $post->post_status ) {
					$post_type_obj = get_post_type_object( $post->post_type );
					if ( ! $post_type_obj || ! current_user_can( $post_type_obj->cap->publish_posts ) ) {
						return false;
					}
				}

				return true;
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);

	// =========================================================================
	// core/delete-post - Delete a post (trash or permanent)
	// =========================================================================
	wp_register_ability(
		'core/delete-post',
		array(
			'label'               => __( 'Delete Post' ),
			'description'         => __( 'Moves a post to trash or permanently deletes it.' ),
			'category'            => $category,
			'input_schema'        => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'    => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the post to delete.' ),
					),
					'force' => array(
						'type'        => 'boolean',
						'description' => __( 'If true, permanently delete instead of moving to trash.' ),
						'default'     => false,
					),
				),
				'required'             => array( 'id' ),
				'additionalProperties' => false,
			),
			'output_schema'       => array(
				'type'                 => 'object',
				'properties'           => array(
					'id'              => array(
						'type'        => 'integer',
						'description' => __( 'The ID of the deleted post.' ),
					),
					'deleted'         => array(
						'type'        => 'boolean',
						'description' => __( 'Whether the post was successfully deleted.' ),
					),
					'previous_status' => array(
						'type'        => 'string',
						'description' => __( 'The status of the post before deletion.' ),
					),
				),
				'required'             => array( 'id', 'deleted' ),
				'additionalProperties' => false,
			),
			'execute_callback'    => static function ( $input = array() ) {
				$post_id = absint( $input['id'] );
				$force   = ! empty( $input['force'] );
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return new WP_Error(
						'post_not_found',
						__( 'Post not found.' ),
						array( 'status' => 404 )
					);
				}

				$previous_status = $post->post_status;

				// wp_delete_post returns the post object on success, false on failure.
				$result = wp_delete_post( $post_id, $force );

				if ( ! $result ) {
					return new WP_Error(
						'delete_failed',
						__( 'Failed to delete post.' ),
						array( 'status' => 500 )
					);
				}

				return array(
					'id'              => $post_id,
					'deleted'         => true,
					'previous_status' => $previous_status,
				);
			},
			'permission_callback' => static function ( $input = array() ): bool {
				if ( empty( $input['id'] ) ) {
					return false;
				}

				$post_id = absint( $input['id'] );
				$post    = get_post( $post_id );

				if ( ! $post ) {
					return false;
				}

				return current_user_can( 'delete_post', $post_id );
			},
			'meta'                => array(
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => true,
					'idempotent'  => true,
				),
				'show_in_rest' => true,
			),
		)
	);
}
