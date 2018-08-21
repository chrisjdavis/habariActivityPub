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
        "outbox": "<?php URL::out( 'v1_usernamefeed', array('username' => $profile->username) ); ?>",
        "preferredUsername": "<?php echo $profile->username; ?>",
        "manuallyApprovesFollowers": "false",
        "displayName": "<?php echo $profile->info->displayname; ?>",
        "summary": "<?php echo $profile->info->apub_summary; ?>",
		"icon": {
			"type": "Image",
			"mediaType": "image/jpeg",
			"url": "https://avatars.io/twitter/<?php echo $profile->username; ?>"
		},
		"image": {
			"type": "Image",
			"mediaType": "image/jpeg",
			"url": "<?php URL::out( 'display_apub_profile', array('username' => $profile->username) ); ?>/header.jpg"
		}
      }
    </script>	
  </head>
  <body>
	  <?php if( aPub::is_following( $user, $profile ) ) { ?>
	  <form id="follow_form" method="post" action="<?php URL::out( 'auth_ajax', Utils::WSSE( array('context' => 'unfollow')) ); ?>">
		  <input type="hidden" name="user_id" value="<?php echo $profile->id; ?>">
		  <button>Stop Following <?php echo $profile->info->displayname; ?></button>
	  <?php } else { ?>
	  <form id="follow_form" method="post" action="<?php URL::out( 'auth_ajax', Utils::WSSE( array('context' => 'follow')) ); ?>">
		  <input type="hidden" name="user_id" value="<?php echo $profile->id; ?>">
		  <button>Follow <?php echo $profile->info->displayname; ?></button>		  		  
	  <?php } ?>
	  </form>
  </body>
</html>