<?php

/**
* -----------------------------------------------------
* -----------------------------------------------------
* ---------- Download-Mailchimp Connector -------------
* -----------------------------------------------------
* -----------------------------------------------------
*/




/**
*   PHP Request Handler
*   Calls the addToMailchimp() function, redirects the user and writes a success message
*   @usage admin_post_nopriv_$action | $action = hidden html input field to hook in
*/
add_action( 'admin_post_nopriv_process_form', 'process_form_data' );
add_action( 'admin_post_process_form', 'process_form_data' );
function process_form_data() {
  addToMailchimp($_POST['dl_email'],$_POST['dl_title'],$_POST['mc_list']);
  wp_redirect($_POST['url']);
}


/**
*  Adds the user to the mailchimp list
*  (Create a automation campaign in mailchimp, which adds user from this list)
*/
function addToMailchimp($user_mail,$download,$mc_list){

  //<interest1-id> example: 'newsletter-registration'
  //<interest2-id> example: specific download-name


  //Hier wird nach dem Download gesucht, welche im Shortcode angegeben wurde
  switch ($download) {
      case "download1":
          $interests = array('<interest1-id>'=>true,'<interest1-id>'=>true);
          break;
      case "download2":
          $interests = array('<interest1-id>'=>true,'<interest2-id>'=>true);
          break;
      case "download3":
          $interests = array('<interest1-id>'=>true,'<interest2-id>'=>true);
          break;
  }

  //Add member, with the interest "newsletter (download)" and a specific download, to list 'selpers'
  do_action('memberToList', '<api-key>','addUserToList', $user_mail, $mc_list, $interests);

  return $result;
}




/**
*   Create the download-link button for newsletter registration
*   Shortcode usage: HTML [download_button download='my-download']
*   Using with specific Mailchimp list: HTML [download_button download='my-download' list='<my-list-id>']
*   @return string the html form
*/
function createDownloadButton($atts){
  //shortcode input
  $a = shortcode_atts( array(
      'download' => '',
      'list' => '<my-list-id>'
  ), $atts );

  $current_url="//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];

  $result = '<form class="ed_form" method="post" action="' .  admin_url( 'admin-post.php' ) . '">';
  $result .= '<div rel="text" class="popup-newsletter_email">';
    $result .= '<br>';
    $result .= '<span class="ed_css_txt">';
      $result .= '<input autofocus class="ed_tb_css" name="dl_email" id="ed_txt_em" placeholder="Your Email*" value="" maxlength="225" type="email" required>'; //speedy
      $result .= '<input hidden name="dl_title" value="'.$a['download'].'"  type="text" >';
      $result .= '<input hidden name="mc_list" value="'.$a['list'].'"  type="text" >';
      $result .= '<input type="hidden" name="action" value="process_form">';
      $result .= '<input type="hidden" name="url" value="'.$current_url.'">';
    $result .= '</span>';
  $result .= '</div>';

  //Track event on google analytics
  $result .= '<p>';
  $result .= '<input class="ed_bt_css" id="ed_btn" onClick="ga(\'send\', \'event\', \'download-newsletter-popup | signIn\', \''.$a['download'].'\');" value="Send download" type="submit">';
  $result .= '</p>';


  $result .= '</form>';

  return $result;
}
add_shortcode( 'download_button', 'createDownloadButton' );
