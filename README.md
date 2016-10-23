# Community Reviews

### Requirements
- PHP >= 7.0.0
- https://github.com/frostschutz/MyBB-PluginLibrary
- http://fontawesome.io/ >= 4.6

### 3rd party integrations
- https://github.com/MyBBStuff/MyAlerts

### 3rd party software
- https://github.com/blueimp/Gallery

### Widgets
 - `{$community_reviews_widget}` - latest reviews widget (`index_end` hook)
 - `{$community_reviews_merchant_widget}` - merchant's reviews (`member_profile_end` hook)

### Plugin management events
- **Install:**
  - Database structure created/altered & populated
  - Settings populated
- **Uninstall:**
  - Database structure & data deleted
  - Settings deleted
  - 3rd party integrations uninstalled
- **Activate:**
  - Templates & stylesheets inserted
- **Deactivate:**
  - Templates & stylesheets removed

### Development mode
The plugin can operate in a development mode, where plugin templates are being fetched directly from the `inc/plugins/community_reviews/templates/` directory - set `CommunityReviews::DEVELOPMENT_MODE` to `true` in `inc/plugins/community_reviews.php`. To enable faster styling you can also modify the `<head>` section by add CSS files directly from `inc/plugins/community_reviews/stylesheets/`.
