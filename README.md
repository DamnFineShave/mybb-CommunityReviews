# Community Reviews

### Requirements
- PHP >= 7.0.0
- MySQL >= 5.6 (MariaDB >= 10.0.5) with InnoDB
- https://github.com/frostschutz/MyBB-PluginLibrary
- http://fontawesome.io/ >= 4.6

### 3rd party integrations
- https://github.com/MyBBStuff/MyAlerts

### 3rd party software
- https://github.com/blueimp/Gallery

### Widgets
 - `{$community_reviews_widget}` - latest reviews widget (`index_end` hook)
 - `{$community_reviews_merchant_widget}` - merchant's reviews (`member_profile_end` hook)
 - `{$community_reviews_user_widget}` - user's own reviews (`member_profile_end` hook)
 
 ### Misc Edits
- Increase General Thoughts character limit, change 2000 to whatever you want in: 
https://github.com/DamnFineShave/mybb-CommunityReviews/blob/master/inc/plugins/community_reviews/logic_frontend.php#L144

- As well as changing text to longtext here:
https://github.com/DamnFineShave/mybb-CommunityReviews/blob/master/inc/plugins/community_reviews.php

### URL rewrite rules
 - Apache

   ```
   RewriteEngine On

   RewriteRule ^reviews$ index.php?action=reviews [L,QSA]
   RewriteRule ^reviews-search$ index.php?action=reviews&search=1 [L,QSA]
   RewriteRule ^reviews-category-([0-9]+)-[0-9a-z_-]+$ index.php?action=reviews&category=$1 [L,QSA]
   RewriteRule ^reviews-product-([0-9]+)-[0-9a-z_-]+$ index.php?action=reviews&product=$1 [L,QSA]
   RewriteRule ^reviews-merchant-([0-9]+)$ index.php?action=reviews&merchant=$1 [L,QSA]
   ```
 - nginx

   ```
  rewrite ^/reviews$ /index.php?action=reviews last;
  rewrite ^/reviews-search$ /index.php?action=reviews&search=1 last;
  rewrite ^/reviews-category-([0-9]+)-[0-9a-z_-]+$ /index.php?action=reviews&category=$1 last;
  rewrite ^/reviews-product-([0-9]+)-[0-9a-z_-]+$ /index.php?action=reviews&product=$1 last;
  rewrite ^/reviews-merchant-([0-9]+)$ /index.php?action=reviews&merchant=$1 last;
   ```

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
