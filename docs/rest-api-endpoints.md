# REST API Endpoints

The Abilities API exposes REST routes for managing abilities.

## List Abilities
```bash
GET /wp-json/abilities/v1/list  

Response:

[
  {
    "name": "my_plugin_edit_data",
    "label": "Edit My Plugin Data",
    "description": "Allows editing of data in My Plugin."
  }
]


 2. **Register Ability** :

POST /wp-json/abilities/v1/register
Content-Type: application/json

{
  "name": "my_plugin_view_stats",
  "label": "View Plugin Stats",
  "description": "Allows viewing analytics data."
}
