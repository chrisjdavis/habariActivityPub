<?php namespace Habari; ?>

<!DOCTYPE html>
<html>
  <head>
    <script type="application/ld+json">
      {
        "@context": [
          "http://www.w3.org/ns/activitystreams",
          "http://www.w3.org/ns/activitypub"
        ],
        "@type": "Person",
        "@id": "<?php URL::out( 'display_apub_profile', array('username' => $profile->username) ); ?>",
        "following": "<?php URL::out( 'v1_usernamefollowing', array('username' => $profile->username) ); ?>",
        "followers": "<?php URL::out( 'v1_usernamefollowers', array('username' => $profile->username) ); ?>",
        "inbox": "<?php URL::out( 'v1_usernameinbox', array('username' => $profile->username) ); ?>",
        "outbox": "<?php URL::out( 'v1_usernameoutbox', array('username' => $profile->username) ); ?>",
        "preferredUsername": "<?php echo $profile->username; ?>",
        "displayName": "<?php echo $profile->info->displayname; ?>",
        "summary": "<?php echo $profile->info->apub_summary; ?>",
        "icon": [
          "https://avatars.io/twitter/<?php echo $profile->username; ?>"
        ]
      }
    </script>
  </head>
  <body>
    <!-- Content goes here! -->
  </body>
</html>