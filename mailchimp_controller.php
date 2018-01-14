<?php

/**
 * Mailchimp CONTROLLER
 * Access and modify mailchimp lists, members and camaigns
 *
 * @author      Christoph Murauer
 * @category    murdesign.at
 * @package     Mailchimp
 * @version     1.1
 */


include("speedy_mailchimp_wrapper.php");


/*** ADD ACTION ***/
//Declarate: add_action ( 'hook_name', 'your_function_name', [priority/default=10], [Number of args] );
//Use:       do_action( 'hook_name', $arg1, $arg2, $arg3, .. );
// add_action( 'deleteFromList','mailchimpController', 10, 4 );
// add_action( 'deleteFromCampaign','mailchimpController', 10, 7 );
//add_action( 'MarkMemberAsVip','mailchimpController', 10, 4 );
add_action( 'memberToCampaign','mailchimpController', 10, 7 );
add_action( 'addInterestToMember','mailchimpController', 10, 5 );
add_action( 'memberToList','mailchimpController', 10, 5 );

/*** USAGE ***/
// do_action('memberToList', '<APIKEY>','addUserToList', '', '<LISTID>', '<INTEREST>');
// do_action('memberToCampaign', '<APIKEY>','addMemberToQueue', '', '', '','<workflowID>','<workflowMailIDID>');


/*
   MAILCHIMP API CONTROLLER
   Arguments:
    $key......................api key;
    $operation:
       ("addInterest")........adds a interest to a member
       ("markAsVIP")..........mark a list-member as VIP
       ("addMemberToQueue")...add a mail adress to an automation campain
       ("addUserToList")......add a user to mailchimp list
       ("printJSON")..........print the list as a JSON
       ("removeFromList").....deletes a member of a mailchimp list
       ("removeFromCampaign").....deletes a member of a mailchimp automation campaign
    $user_mail................(optional) select an user
    $list.....................(optional) select a list;
    $interest.................(optional) select a interest categorie (group);
    $workflowID...............(optional) select a automation campaign;
    $workflowMailIDID...........(optional) select an mail of a automation campaing;

    Visit 'mailchimp playground' to get all the resources
*/
function mailchimpController($key="", $operation="", $user_mail="", $list="",$interest="",$workflowID="",$workflowMailID=""){

    if(!$key){
      echo  "<script>console.log('Mailchimp Controller says: Error - There is no API-Key!');</script>";
      return;
    }
    $api = array(
        'login' => 'apikey',
        'key'   => $key,
        'url'   => 'https://usXX.api.mailchimp.com/3.0/'
      );


      //Get current user Email from database
      global $wpdb;
      $user_id = get_current_user_id();
      $sql = "SELECT user_email FROM wp_users WHERE ID = $user_id";

      if(!$list){
        //There is no Argument: Set default 'API' List
        $targetList	= 'lists/<your-debuging-list-id>/';
        //echo "<script>console.log('Mailchimp Controller says: No List selected! Default List: " . $targetList . "' );</script>";
      }else{
        $targetList = 'lists/' . $list . '/';
      }

      if(!$user_mail){
        //No mail in argument means, that the user has to add to list
        $user_mail = $wpdb->get_var($sql);
      }else{
        //There is an individual mail adress; Add it to list, if it isn't already there
        addUserToList($user_mail, $api, $targetList);
      }


      switch ($operation){
        case "":
          //No argument
          echo "<script>console.log('Mailchimp Controller says: No Operation selected! Argument: " . $operation . "' );</script>";
          break;

        case "addInterest":
           //Add interest to member
           addInterest($user_mail, $api, $targetList, $interest);
           break;

        case "markAsVIP":
           //Activate VIP status for user_mail in targetList
           markAsVIP($user_mail, $api, $targetList);
           break;

        case "addMemberToQueue":
          if(!$workflowID || !$workflowMailID){
            echo "<script>console.log('Mailchimp Controller says: No automation campaign or mail selected!');</script>";
            break;
          }
          //Get automation
          $targetAutomation = 'automations/'. $workflowID .'/emails/'. $workflowMailID .'/queue';
          //Add current user_email to targetAutomation
          addMemberToQueue($user_mail, $api, $targetAutomation);
          break;

        case "addUserToList":

          //Add current user email to targetlist
          addUserToList($user_mail, $api, $targetList, 'true', $interest);
          break;

        //TODO
        // case "removeFromList":
        //   removeMemberFromList($user_mail, $api, $targetList);
        //   echo "<script>console.log('Mailchimp Controller says: User removed! Argument: " . $user_mail . "' );</script>";
        //   break;
        //
        // case "removeFromCampaign":
        //   //Get automation
        //   $targetAutomation = 'automations/'. $workflowID .'/emails/'. $workflowMailID .'/queue';
        //   removeMemberFromCampaign($user_mail, $api, $targetAutomation);
        //   break;

        case "printJSON":
          //Print the target as json
          printJSON($api, $targetList);
          break;
      }

}

?>
