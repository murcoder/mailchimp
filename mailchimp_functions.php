<?php

/**
 * Mailchimp wrapper
 *
 * @author      Christoph Murauer
 * @category    murdesign.at
 * @package     Mailchimp
 * @version     1.1
 */


/*	----------------------------------------------------------------------------------------------
    MAILCHIMP REQUEST
    Return: The JSON representation of requested Mailchip-Target
    Description:

      $api = array
      (
      'login' => 'CanBeAnything',
      'key'   => 'YourAPIKey',
      'url'   => 'https://<dc> .api.mailchimp .com/3.0/
      )

      $type		'GET','POST','PUT','PATCH','DELETE'

      $target		Whatever you're after from the API. Could be:
      '', 'lists', lists/abcd12345',
      'lists/abcd12345/members/abcd12345abcd12345abcd12345abcd12345', etc.

      $data		Associative array with the key => values to be passed.
      Don't forget to match the strucutre of whatever you're trying to PATCH
      or PUT. e.g., For patching a member's first name, use:

      $data = array( 'merge_fields' => array( 'FNAME': "New Name" ) );
      mc_request( $my_api_info, 'PATCH',
      'lists/abcd12345/members/abcd12345abcd12345abcd12345abcd12345', $data );

      If you need to tunnel your requests through a service that only supports POST through Curl,
      uncomment the two lines below and comment out:
      curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $type );
      ----------------------------------------------------------------------------------------------
*/
function mc_request( $api, $type, $target, $data = false ){

    try{

        //Bestimme das Ziel zur Resource
        $ch = curl_init( $api['url'] . $target );

        if (FALSE === $ch)
          throw new Exception('failed to initialize');

        //Ein Array von HTTP-Headern, im Format array('Content-type: text/plain', 'Content-length: 100')
        curl_setopt( $ch, CURLOPT_HTTPHEADER, array
        (
          'Content-Type: application/json',
          'Authorization: ' . $api['login'] . ' ' . $api['key']
          //		'X-HTTP-Method-Override: ' . $type,
        ) );
        //DEBUG: echo 'Authorization: ' . $api['login'] . ' ' . $api['key'] . "<br />";

        //	curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'POST' );
        curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $type );
        curl_setopt( $ch, CURLOPT_TIMEOUT, 5 );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0' );

        //TODO - SICHERE SSL VERBINDUNG HERSTELLEN
        //FEHLER: "curl: (60) SSL certificate : unable to get local issuer certificate"
        //http://stackoverflow.com/questions/24611640/curl-60-ssl-certificate-unable-to-get-local-issuer-certificate
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        if( $data ){
          curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
          //echo "There is data";
        }


        $response = curl_exec( $ch );

        if (FALSE === $response)
          throw new Exception(curl_error($ch), curl_errno($ch));

        curl_close( $ch );

  } catch(Exception $e) {

      trigger_error(sprintf(
          'Curl failed with error #%d: %s',
          $e->getCode(), $e->getMessage()),
          E_USER_ERROR);

  }


    return $response;
}




// TODO
// function removeMemberFromList($user_mail, $api, $target){
//   $targetList = $target . 'members/';
//   //Set mail adress pasing throw
//   $data = array(
//     'email_address' => $user_mail
//   );
//     $jsonfile = mc_request($api, 'DELETE', $targetList, $data);
//     //echo "<script>console.log('Mailchimp Wrapper - removeMemberFromList || Jsonfile: . $jsonfile . ' );</script>";
//
// }
//
// function removeMemberFromCampaign($user_mail, $api, $automationTarget){
//   //Set mail adress pasing throw
//   $data = array(
//     'email_address' => $user_mail
//   );
//     $jsonfile = mc_request($api, 'DELETE', $automationTarget, $data);
// }





/*
/   Add a user to automation campaign in mailchimp
*/
function addMemberToQueue($user_mail, $api, $automationTarget){


  //Set mail adress pasing throw
  $data = array(
    'email_address' => $user_mail
  );

    $jsonfile = mc_request($api, 'POST', $automationTarget, $data);
    //echo "<script>console.log('Mailchimp Wrapper - addMemberToQueue | member: '.$user_mail.' to '.$automationTarget.' | Jsonfile: '<br />'. $jsonfile . ' );</script>";

}


function addUserToList($user_mail, $api, $target, $mergeField="false", $interest=""){

  $user_id = get_current_user_id();
  $firstname = get_user_meta($user_id, 'first_name', 'true');
  $lastname = get_user_meta($user_id, 'last_name', 'true');
  $gender = get_user_meta($user_id, 'Anrede', 'true');
  $anrede = "";

  if(!empty($gender)){
   if(strcmp($gender,"Frau") == 0){
     $anrede = "Liebe";
   }else if(strcmp($gender,"Herr") == 0){
     $anrede = "Lieber";
   }
  }

  // echo "<script>console.log('userid: ".$user_id."; firstname: ".$firstname."; lastname: ".$lastname."; Anrede ".$anrede."' );</script>";

  $targetList = $target . 'members/';



  if(!is_array($interest)){
    //initialize interests
    $interests = array();
    if($interest){
      $interests = array(
        $interest => true
      );
    }
  }else{
    $interests = $interest;
}



  //create the member, depending on the mergefields
  if(!$mergeField){
     $data = array(
       'email_address' => $user_mail,
       'status'        => "subscribed",
       'interests' => $interests
     );
   }else if($mergeField){
     $fields = array(
       'FNAME' => $firstname,
       'LNAME'  => $lastname,
       'ANREDE' => $anrede
     );
     $data = array(
       'email_address' => $user_mail,
       'status'        => "subscribed",
       'merge_fields'  => $fields,
       'interests' => $interests
     );
   }

    $jsonfile = mc_request($api, 'POST', $targetList, $data);
    echo "<script>console.log('Mailchimp Wrapper - AddMemberToList Jsonfile:  . $jsonfile . ' );</script>";

}



/*
    Set the VIP-Status of a member to TRUE
*/
function markAsVIP($user_mail, $api, $targetList){

  //Set VIP Status to true
   $data = array(
     'vip' => true);

     $target = $targetList . 'members/';

     //Get the URL of the member with this user_mail of the targetlist
     $memberTarget = getMemberTarget($api, $target, $user_mail);

     $jsonfile = mc_request($api, 'PATCH', $memberTarget, $data);
     //echo "<script>console.log('Mailchimp Wrapper Jsonfile:  . $jsonfile . ' );</script>";
}

/*
    Add a interest to a member
*/
function addInterest($user_mail, $api, $targetList, $interest){

  //Set VIP Status to true
   $data = array(
     'interests' => array( $interest => true)
   );

     $target = $targetList . 'members/';

     //Get the URL of the member with this user_mail of the targetlist
     $memberTarget = getMemberTarget($api, $target, $user_mail);
     //echo "<script>console.log('Membertarget: . $memberTarget . ; target:. $target. ; mail: . $user_mail. ' );</script>";


     $jsonfile = mc_request($api, 'PATCH', $memberTarget, $data);
     //echo "<script>console.log('Mailchimp Wrapper - AddInterest Jsonfile: " . $jsonfile . "' );</script>";
}

  /*
      Returns the URL of a member in a specific mailchimp list for PATCH purpose
  */
  function getMemberTarget($api, $targetList, $user_mail){

    //Get the Mailchimp list as JSON
    $jsonList = mc_request($api, 'GET', $targetList);
    $jsonToObject = json_decode($jsonList);

    //looking for db-user in Mailchimp list
    foreach ($jsonToObject->members as $member) {

            //Check if Mail is also in Mailchimp | compare strings: 0 means equal
            if( strcmp( $user_mail, $member->email_address) == 0){

              $email = strtolower(trim($user_mail));

               //create email_hash to access member via mailchimp
              $emailHash = md5($email);

              //build target-url for mailchimp
              $memberTarget	= $targetList . $emailHash;
              //echo "member Target: " . $memberTarget . "<br />";

              return $memberTarget;
            }
      }
  }



/*
    Print the target as json
*/
function printJSON($api, $target){


  //Get the Mailchimp list as JSON
  $jsonList = mc_request($api, 'GET', $target);
  $jsonToObject = json_decode($jsonList);
  echo $jsonList;
}


?>
