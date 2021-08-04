<?php 
/*
*
*	***** TBL Firebase auth *****
*
*	Core Functions
*	
*/
// If this file is called directly, abort. //
if ( ! defined( 'WPINC' ) ) {die;} // end if
/*
*
* Custom Front End Ajax Scripts / Loads In WP Footer
*
*/
function tfa_frontend_ajax_form_scripts(){global $post;
	if ( has_shortcode( $post->post_content, 'register_form' ) ) {
?>
<script type="text/javascript">
jQuery(document).ready(function($){
    "use strict";
	 
		function readURL(input) {
		  if (input.files && input.files[0]) {
			 var reader = new FileReader();
			 
			 reader.onload = function(e) {
			
				$(input).closest('div').find('.upload_image_prev').attr('src', e.target.result);
			 }
			 reader.readAsDataURL(input.files[0]); // convert to base64 string
		  }
		}

		$(".upload_image").on('change',function() { 
			// alert(798798)
		  readURL(this);
		});
	 
	 
	function serializeToJson(serializer){
		 var _string = '{';
		 for(var ix in serializer)
		 {
			  var row = serializer[ix];
			  _string += '"' + row.name + '":"' + row.value + '",';
		 }
		 var end =_string.length - 1;
		 _string = _string.substr(0, end);
		 _string += '}';
	//	 console.log('_string: ', _string);
		 return JSON.parse(_string);
	}

    // add basic front-end ajax page scripts here
    $('#tfa_registration_form').submit(function(event){
			
			$('#reg_status_msg_wp').html('<div class="loader"></div>');	
			
        event.preventDefault();
		  
		//  var formInputs = JSON.stringify($('form#tfa_registration_form').serializeArray());
		//	var formInputs = $('form#tfa_registration_form').serializeArray();
		//	formInputs = serializeToJson(formInputs);
		//	console.log(document.getElementsByName('name')['tfa_user_email'].value);
			var user_email = document.getElementsByName("tfa_user_email")[0].value;
			var user_pass = document.getElementsByName("tfa_user_pass")[0].value;
		//	console.log(document.getElementsByName("tfa_user_email")[0].value);
			var userType = 'patient';
			if( document.getElementsByName("tfa_user_type")[0].value == 'provider'){
				userType = 'provider';
			}
			firebase.auth().createUserWithEmailAndPassword(user_email,user_pass).then(function(res){
				console.log('firebase_res:',res.user.uid);
				var fbUid = res.user.uid;
				
				/* ================== WP Registration ================== */
				var myInputFieldValue = $('#myInputField').val();
			  // Ajaxify the Form
				var data = {
					'action': 'tfa_custom_reg_ajax',
					'inputFieldValues':   $('form#tfa_registration_form').serialize(),
					'fbUid': fbUid
				};
				var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
				$.post(ajaxurl, data, function(response) {
						 response = JSON.parse(response);
						 console.log(response);
						 if(response.status == 'success')
						 {
							/* ========== User data add to Firebase Database ========== */
							firebase.database().ref('users/'+ userType +'/' + fbUid).set({
								firstName: document.getElementsByName("tfa_user_first")[0].value,
								lastName: document.getElementsByName("tfa_user_last")[0].value,
								email: user_email
							}).then(function(result){
								
								
									$('#reg_status_msg_wp').html('<p style="color:green;"><b><i>('+response.email+')</i> Registration Success! Now Redirecting to Login, Please wait.</b></p>');	
									$('form .form_in').hide('slow');
									window.location.href = "<?php echo home_url('/registration-login?msg=Registration-Success!'); ?>";
							});
							
							 
							console.log('response.userdata:',response.userdata);
							//  $('#tfa_custom_plugin_form_wrap').html(response);	
							
						 }else{ /* ============ if WP registration error ============ */
						 
								console.log('response.message:',response.message);
								
								var user = firebase.auth().currentUser;

								user.delete().then(function() {
								  console.log('user.delete.message:','User removed from FB');
								}).catch(function(error) {
								  console.log('user.delete.error:','User removing problem from FB');
								});
								
								$('#reg_status_msg_wp').html('<p style="color:red;"><b><i>('+response.email+')</i> Web Registration error.</b><br>'+response.errors+'</p>');
						 }
				});
				
			//	$('#reg_status_msg_fb').html('<p style="color:green;"><b><i>('+user_email+')</i> Firebase Registration success.</b></p>');
				
			}).catch(function(error){
				console.log('firebase_res e:',error);
				$('#reg_status_msg_fb').html('<p style="color:red;"><b><i>('+response.email+')</i> Firebase Registration error.</b><br>'+error+'</p>');
			});
		  
		  /*
        // Vars
        var myInputFieldValue = $('#myInputField').val();
        // Ajaxify the Form
        var data = {
            'action': 'tfa_custom_reg_ajax',
            'inputFieldValues':   $('form#tfa_registration_form').serialize(),
        };
			
        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
        var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
        $.post(ajaxurl, data, function(response) {
                response = JSON.parse(response);
                console.log(response);
                if(response.status == 'success')
                {
						$('#reg_status_msg_wp').html('<p style="color:green;"><b><i>('+response.email+')</i> '+response.message+'</b></p>');	
						$('form .form_in').hide('slow');
						 
                  console.log('response.message:',response.message);
                  //  $('#tfa_custom_plugin_form_wrap').html(response);	
                }
                else
                {
							console.log('response.message:',response.message);
							$('#reg_status_msg_wp').html('<p style="color:red;"><b><i>('+response.email+')</i> Web Registration error.</b><br>'+response.message+'</p>');
                }
        });
		  */
    });

		/* ========= Get firebase user detail ========= */
		function getUserData(uid) {
			 firebase.database().ref('users').once("value", snap => {
				 var retData = snap.val();
				  console.log('snap.val():',snap.val())
			 })
		}
		
		
    $('#tfa_login_form').submit(function(event){
			
			$('#login_status_msg_wp,#login_status_msg_fb').html('<div class="loader"></div>');	
			
			event.preventDefault();
			// Vars
		//	var myInputFieldValue = $('#myInputField').val();
			// Ajaxify the Form
			
			var user_email = document.getElementsByName("tfa_user_email_log")[0].value;
			var user_pass = document.getElementsByName("tfa_user_pass_log")[0].value;

			firebase.auth().signInWithEmailAndPassword(user_email,user_pass).then(function(res){
				console.log('firebase_res:',res.user.uid);	
				
				$('#login_status_msg_fb').html('<p style="color:green;"><b><i>('+user_email+')</i> Firebase Login success.</b></p>');	
				
				/* ============== WP login ============== */
	
				var data = {
					'action': 'tfa_custom_login_ajax',
					'inputFieldValues':   $('form#tfa_login_form').serialize(),
				};
				
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				var ajaxurl = "<?php echo admin_url('admin-ajax.php');?>";
				$.post(ajaxurl, data, function(response) {
					 response = JSON.parse(response);
					 console.log(response);
					
					 if(response.status == 'success')
					 {
							console.log('response:WP login success ::-',response.response.data.user_email);
							console.log('response.data.user_email:-',response.response.data.user_email);
							 
							console.log('custom_data:',response.response.data.custom_data);	
							$('#login_status_msg_wp').html('<p style="color:green;"><b><i>('+response.response.data.user_email+')</i> '+response.message+'</b></p>');	
							window.location.href = "<?php echo home_url('/profile'); ?>";
					//	 	$('form .form_in').hide('slow');
					//	
					
							console.log('response.message_both:',response.message);
						// $('#tfa_custom_plugin_form_wrap').html(response);	
					 }else{
							window.location.replace("<?php echo esc_url(wp_logout_url()); ?>");
							console.log('response.message:',response.message);
							$('#login_status_msg_wp').html('<p style="color:red;"><b><i>('+response.response.data.user_email+')</i> Web Login error.</b><br>'+response.response.message+'</p>');
					 }
					 
				});
				
			}).catch(function(error){
				console.log('firebase_res e:',error);
				$('#login_status_msg_fb').html('<p style="color:red;"><b><i>('+response.response.data.user_email+')</i> Firebase Login error.</b><br>'+error+'</p>');
			});
			
    });
	 
		/*
	 $("#tfa_user_type").on('change',function(){
		 if( $(this).val() == "patients" ){
			 console.log("patients selected");
			 $("label[for='tfa_user_Login']").text("Username");
			 $(".for_patients").show();
		 }else if($(this).val() == "provider"){
			 console.log("provider selected");
			 $("label[for='tfa_user_Login']").text("NPI NO.");
			 $(".for_patients").hide();
		 }
	 });
		*/
		
		
		/* ============================= */
		/* ============================= */
			var ddata = {
  "DKA5G61sfbd01mAU93rwSlpsZ" : {
    "address1" : "16 state street, 20th floor",
    "address2" : "ny 10004",
    "city" : "New York",
    "email" : "thomasdesign@yopmail.com",
    "firstName" : "tom",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Friends",
    "lastName" : "fin",
    "location" : {
      "lat" : 41.1127753,
      "lng" : -74.9059728
    },
    "middleName" : "wood",
    "npiNumber" : "1234",
    "numberOfProvinceInClinic" : "1–5",
    "officeNumber" : "2127390165",
    "phoneNumber" : "2127390166",
    "prefix" : "Dr",
    "profileImage" : "",
    "serviceType" : "Hand Surgery",
    "speciality" : "Something",
    "state" : "NY",
    "suffix" : "Sr",
    "taxonomy" : "Pain",
    "uid" : "DKA5G61sfbd01mAU93rwSlpsZ",
    "zipCode" : "10001"
  },
  "KdDpyNJUgLH4rDPg7XiiHmis" : {
    "address1" : "8500, Peña Blvd",
    "address2" : "",
    "city" : "Denver",
    "email" : "tompro222@yopmail.com",
    "firstName" : "Thomas",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Facebook",
    "lastName" : "Woodfin",
    "location" : {
      "lat" : 39.1560963,
      "lng" : -103.0737376
    },
    "middleName" : "W",
    "npiNumber" : "4852588",
    "numberOfProvinceInClinic" : "3",
    "officeNumber" : "8885018555",
    "phoneNumber" : "8885018555",
    "prefix" : "Prof",
    "profileImage" : "",
    "serviceType" : "Select Service",
    "speciality" : "Something Else",
    "state" : "CO",
    "suffix" : "PhD",
    "taxonomy" : "Podiatrist",
    "uid" : "KdDpyNJUgLH4rDPg7XiiHmis",
    "zipCode" : "80249"
  },
  "QHAYCiYERyD7FClgt05kac3fk" : {
    "address1" : "1390 W. 5th Ave.",
    "address2" : "1390 W. 5th Ave.",
    "city" : "columbus",
    "email" : "joebuffalo270@yopmail.com",
    "firstName" : "julex",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Email",
    "lastName" : "gabutin",
    "location" : {
      "lat" : 38.1560963,
      "lng" : -102.0737376
    },
    "middleName" : "gabutin",
    "npiNumber" : "09424507996",
    "numberOfProvinceInClinic" : "1–5",
    "officeNumber" : "12345678",
    "phoneNumber" : "09424507996",
    "prefix" : "Mr",
    "profileImage" : "",
    "serviceType" : "Addiction Medicine",
    "speciality" : "Something",
    "state" : "OH",
    "suffix" : "None",
    "taxonomy" : "Physical",
    "uid" : "QHAYCiYERyD7FClgt05kac3fk",
    "zipCode" : "43212"
  },
  "RsR5eKVETrKJkakWCji5KGyyH" : {
    "address1" : "3077, El Camino Real",
    "address2" : "",
    "city" : "Santa Clara",
    "email" : "playstorecnx2@yopmail.com",
    "firstName" : "John",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Email",
    "lastName" : "adam",
    "location" : {
      "lat" : 37.3528937,
      "lng" : -121.9826193
    },
    "middleName" : "Joe",
    "npiNumber" : "123456789",
    "numberOfProvinceInClinic" : "1–5",
    "officeNumber" : "9108509570",
    "phoneNumber" : "9108509570",
    "prefix" : "Mr",
    "profileImage" : "",
    "serviceType" : "Allergy/Immunology",
    "speciality" : "Something",
    "state" : "CA",
    "suffix" : "None",
    "taxonomy" : "Podiatrist",
    "uid" : "RsR5eKVETrKJkakWCji5KGyyH",
    "zipCode" : "95051"
  },
  "Su3cvGBGU3pWVbjrNg3TQOByI" : {
    "address1" : "test 1",
    "address2" : "test 2",
    "city" : "Cape Coral",
    "email" : "patient00@yopmail.com",
    "firstName" : "Thomas",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "How Do You Hear About Us How Do You Hear About Us",
    "lastName" : "Woodfin",
    "location" : {
      "lat" : 36.1606364,
      "lng" : -117.1674865
    },
    "middleName" : "Tom",
    "npiNumber" : "322",
    "numberOfProvinceInClinic" : "",
    "officeNumber" : "322322322",
    "phoneNumber" : "444467897",
    "prefix" : "Lorem",
    "profileImage" : "https://firebasestorage.googleapis.com/v0/b/patient-access-365.appspot.com/o/users%2Fimages%2FNjSu3cvGBGU3pWVbjrNg3TQOByI3.jpg?alt=media&token=a4811301-3d0a-499d-a5f5-464f49a64f91",
    "serviceType" : "Addiction Medicine",
    "speciality" : "Something",
    "state" : "FL",
    "suffix" : "Ipsum",
    "taxonomy" : "Test",
    "uId" : "Su3cvGBGU3pWVbjrNg3TQOByI",
    "zipCode" : "322"
  },
  "iYtcRQH8f4SdNPVunzCWhgHng" : {
    "address1" : "8500, Peña Blvd",
    "address2" : "",
    "city" : "Denver",
    "email" : "tompro198@yopmail.com",
    "firstName" : "Thomas",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Friends",
    "lastName" : "Woodfin",
    "location" : {
      "lat" : 38.9560963,
      "lng" : -103.6737376
    },
    "middleName" : "2",
    "npiNumber" : "2554588",
    "numberOfProvinceInClinic" : "9",
    "officeNumber" : "8885018555",
    "phoneNumber" : "8885018555",
    "prefix" : "Prof",
    "profileImage" : "",
    "serviceType" : "Select Service",
    "speciality" : "Something",
    "state" : "CO",
    "suffix" : "PhD",
    "taxonomy" : "Dentist",
    "uid" : "iYtcRQH8f4SdNPVunzCWhgHng",
    "zipCode" : "80249"
  },
  "lgt7rgSRUckG8uBSsbQL68L0t" : {
    "address1" : "1001, Rose Bowl Drive",
    "address2" : "",
    "city" : "Pasadena",
    "email" : "twoodfin07@yopmail.com",
    "firstName" : "thomsd ",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Facebook",
    "lastName" : "woodfkn ",
    "location" : {
      "lat" : 35.1606364,
      "lng" : -117.1674865
    },
    "middleName" : "w",
    "npiNumber" : "121111",
    "numberOfProvinceInClinic" : "6",
    "officeNumber" : "8459438855",
    "phoneNumber" : "8459438855",
    "prefix" : "Mr",
    "profileImage" : "",
    "serviceType" : "Select Service",
    "speciality" : "Something Else",
    "state" : "CA",
    "suffix" : "Sr",
    "taxonomy" : "Pain",
    "uid" : "lgt7rgSRUckG8uBSsbQL68L0t",
    "zipCode" : "91103"
  },
  "sCziTu6hdoGVIX5PyvGJ7Nvs4" : {
    "address1" : "Address 1",
    "address2" : "",
    "city" : "Denver",
    "email" : "tomdoc922@yopmail.com",
    "firstName" : "Thomas",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "lgkjhlkj",
    "lastName" : "Woodfin 9",
    "location" : {
      "lat" : 29.1628537,
      "lng" : -80.9495331
    },
    "middleName" : "W",
    "npiNumber" : "",
    "numberOfProvinceInClinic" : "-9-=9impngbrfulg",
    "officeNumber" : "--0897uighlhkit7y",
    "phoneNumber" : "8885018555",
    "prefix" : "-=90=u9ijo jnkl.,mlhvkgv ",
    "profileImage" : "https://firebasestorage.googleapis.com/v0/b/patient-access-365.appspot.com/o/users%2Fimages%2FonsCziTu6hdoGVIX5PyvGJ7Nvs42.jpg?alt=media&token=21d34099-8d74-4710-b28d-6791400f47e2",
    "serviceType" : "-87",
    "speciality" : "-9768",
    "state" : "CO",
    "suffix" : "[0-90-ik[mpj' /mk.[0j8pynl,",
    "taxonomy" : "---07fjyfjh",
    "uId" : "sCziTu6hdoGVIX5PyvGJ7Nvs4",
    "zipCode" : "10001"
  },
  "uREuMbtvT7TnZUwoZ2LaeAZCh" : {
    "address1" : "test 1",
    "address2" : "test 2",
    "city" : "Cape Coral",
    "email" : "user22345@yopmail.com",
    "firstName" : "Thomas2",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "How Do You Hear About Us How Do You Hear About Us",
    "lastName" : "Woodfin2",
    "location" : {
      "lat" : 37.1606364,
      "lng" : -118.1674865
    },
    "middleName" : "Tom",
    "npiNumber" : "322",
    "numberOfProvinceInClinic" : "",
    "officeNumber" : "322322322",
    "phoneNumber" : "444467897",
    "prefix" : "Lorem",
    "profileImage" : "https://firebasestorage.googleapis.com/v0/b/patient-access-365.appspot.com/o/users%2Fimages%2FNjSu3cvGBGU3pWVbjrNg3TQOByI3.jpg?alt=media&token=a4811301-3d0a-499d-a5f5-464f49a64f91",
    "serviceType" : "Addiction Medicine",
    "speciality" : "Something",
    "state" : "FL",
    "suffix" : "Ipsum",
    "taxonomy" : "Test",
    "uId" : "uREuMbtvT7TnZUwoZ2LaeAZCh",
    "zipCode" : "322"
  },
  "yxBm4ndgKCVSn1tokzCA9juT" : {
    "address1" : "tes",
    "address2" : "test",
    "city" : "Cape Coral",
    "email" : "prov00023@yopmail.com",
    "firstName" : "test",
    "gender" : "Male",
    "howDoYouHearAboutUs" : "Ad",
    "lastName" : "test",
    "location" : {
      "lat" : 28.5628537,
      "lng" : -81.9495331
    },
    "middleName" : "test",
    "npiNumber" : "2131313123213",
    "numberOfProvinceInClinic" : "6–10",
    "officeNumber" : "0123131",
    "phoneNumber" : "1312312312312",
    "prefix" : "Mr",
    "profileImage" : "",
    "serviceType" : "Advanced Heart Failure and Trans..",
    "speciality" : "Something",
    "state" : "FL",
    "suffix" : "III",
    "taxonomy" : "Cardiologist",
    "uid" : "yxBm4ndgKCVSn1tokzCA9juT",
    "zipCode" : "2313213"
  }
};
			
			
			 ddata.forEach(obj => {
				  Object.entries(obj).forEach(([key, value]) => {
						console.log(`${key} ${value}`);
				  });
				  console.log('-------------------');
			 });
			
			/*
			var userEmail = ;
			var userPass = ;
			firebase.auth().createUserWithEmailAndPassword(userEmail,userPass).then(function(res){
				console.log('firebase_res:',res.user.uid);
				var fbUid = res.user.uid;
	

				var data2Set = {};
				data2Set.firstName = document.getElementById("firstName").value;
				data2Set.middleName = document.getElementById("middleName").value;
				data2Set.lastName = document.getElementById("lastName").value;
				data2Set.email = userEmail;
				data2Set.address1 = document.getElementById("address1").value;
				data2Set.address2 = document.getElementById("address2").value;
				data2Set.phoneNumber = document.getElementById("phoneNumber").value;
				data2Set.city = document.getElementById("city").value;
				data2Set.state = document.getElementById("state").value;
				data2Set.zipCode = document.getElementById("zipCode").value;
				data2Set.gender = document.getElementById("gender").value;
				data2Set.profileImage = document.getElementById("profileImage_inp").value;
				data2Set.uId = fbUid;					
					data2Set.npiNumber = document.getElementById("npiNumber").value;
					data2Set.serviceType = document.getElementById("serviceType").value;
					data2Set.speciality = document.getElementById("speciality").value;
					data2Set.taxonomy = document.getElementById("taxonomy").value;
					data2Set.howDoYouHearAboutUs = document.getElementById("howDoYouHearAboutUs").value;
					data2Set.officeNumber = document.getElementById("officeNumber").value;
					data2Set.numberOfProvinceInClinic = document.getElementById("numberOfProvinceInClinic").value;
					data2Set.prefix = document.getElementById("prefix").value;
					data2Set.suffix = document.getElementById("suffix").value;
	
	
	
				// ========== User data add to Firebase Database ========== 
				firebase.database().ref('users/provider/' + fbUid).set(data2Set).then(function(result){
					console.log("User registered: "+ userEmail + " / Pass: " + userPass);
				});
			//	$('#reg_status_msg_fb').html('<p style="color:green;"><b><i>('+user_email+')</i> Firebase Registration success.</b></p>');
				
			}).catch(function(error){
				console.log('firebase_res e:',error);
				
			});
		*/
		
		
		
		
		
		
		
		
		
		
		
}(jQuery));    
</script>
<?php } }
 add_action('wp_footer','tfa_frontend_ajax_form_scripts', 100);





function tfa_firebase_config(){
?>
	<!-- The core Firebase JS SDK is always required and must be listed first -->
	<script src="https://www.gstatic.com/firebasejs/8.4.1/firebase-app.js"></script>

	<!-- TODO: Add SDKs for Firebase products that you want to use
		  https://firebase.google.com/docs/web/setup#available-libraries -->
	<script src="https://www.gstatic.com/firebasejs/8.4.1/firebase-analytics.js"></script>
	<script src="https://www.gstatic.com/firebasejs/8.4.1/firebase-auth.js"></script>
	<script src="https://www.gstatic.com/firebasejs/8.4.1/firebase-database.js"></script>
	<script src="https://www.gstatic.com/firebasejs/8.4.1/firebase-storage.js"></script>

	<script>
	  // Your web app's Firebase configuration
	  // For Firebase JS SDK v7.20.0 and later, measurementId is optional
	  var firebaseConfig = {
    apiKey: "AIzaSyAT681d6MpMO_4owsFV4j2rW892mBXm2Mg",
    authDomain: "patient-access-365.firebaseapp.com",
    databaseURL: "https://patient-access-365.firebaseio.com",
    projectId: "patient-access-365",
    storageBucket: "patient-access-365.appspot.com",
    messagingSenderId: "175048051433",
    appId: "1:175048051433:web:78d89e79f14faecb043e78",
    measurementId: "G-02RVN1Z7G7"};
	  // Initialize Firebase
	  firebase.initializeApp(firebaseConfig);
	  firebase.analytics();
	  
		function reg_to_firebase(fb){
			
			fb.auth().createUserWithEmailAndPassword('user1@yopmail.com','upass123').then(function(res){
				console.log(res);
			}).catch(function(error){
				console.log(error);
			});
		}
	//	reg_to_firebase(firebase);
		
	</script> 
<?php }
add_action('wp_head','tfa_firebase_config', 100);

 


// user registration login form
function tfa_registration_form() {
 
	// only show the registration form to non-logged-in members
	if(!is_user_logged_in()) {
 
		// check if registration is enabled
		$registration_enabled = get_option('users_can_register');
 
		// if enabled
		if($registration_enabled) {
			$output = tfa_registration_fields();
			if( isset($_GET['msg']) ){
				if( $_GET['msg'] == "Registration-Success!" ){
					$output = "<h4>Please login now >></h4>";
				}
			}
		} else {
			$output = __('User registration is not enabled');
		}
		return $output;
	}
}
add_shortcode('register_form', 'tfa_registration_form');

// registration form fields
function tfa_registration_fields() {
	
	ob_start(); ?>	
		<h3 class="tfa_header"><?php _e('Register New Account'); ?></h3>
		<?php 
		// show any error messages after form submission
		tfa_register_messages(); ?>
		
		<form id="tfa_registration_form" class="tfa_form" action="" method="POST">
			<fieldset>
				<br>
				<div class="form_in">
					<p>
						<label for="tfa_user_type"><?php _e('User Type'); ?></label>
						<select name="tfa_user_type" id="tfa_user_type">
							<option value="patients" selected >Patients</option>
							<option value="provider">Doctor/Provider</option>
						</select>
					</p>
					<p>
						<label for="tfa_user_Login"><?php _e('Username'); ?></label><br>
						<input name="tfa_user_login" id="tfa_user_login" class="tfa_user_login" type="text"/>
					</p>
					<p class="">
						<label for="tfa_user_email"><?php _e('Email'); ?></label>
						<input name="tfa_user_email" id="tfa_user_email" class="tfa_user_email" type="email"/>
					</p>
					<p class="for_patients">
						<label for="tfa_user_first"><?php _e('First Name'); ?></label>
						<input name="tfa_user_first" id="tfa_user_first" type="text" class="tfa_user_first" />
					</p>
					<p class="for_patients">
						<label for="tfa_user_last"><?php _e('Last Name'); ?></label>
						<input name="tfa_user_last" id="tfa_user_last" type="text" class="tfa_user_last"/>
					</p>
					<p>
						<label for="password"><?php _e('Password'); ?></label>
						<input name="tfa_user_pass" id="password" class="password" type="password"/>
					</p>
					<p>
						<label for="password_again"><?php _e('Password Again'); ?></label>
						<input name="tfa_user_pass_confirm" id="password_again" class="password_again" type="password"/>
					</p>
					<p>
						<input type="hidden" name="tfa_csrf" value="<?php echo wp_create_nonce('vicode-csrf'); ?>"/>
						<input type="submit" value="<?php _e('Register Your Account'); ?>"/>
					</p>
				</div>
				<div id="reg_status_msg_wp"></div>
				<div id="reg_status_msg_fb"></div>
			</fieldset>
		</form>
	<?php
	return ob_get_clean();
}

// register a new user
function tfa_add_new_user() {
    if (isset( $_POST["tfa_user_login"] ) && wp_verify_nonce($_POST['tfa_csrf'], 'vicode-csrf')) {
      $user_login		= $_POST["tfa_user_login"];	
      $user_email		= $_POST["tfa_user_email"];
      $user_first 	    = $_POST["tfa_user_first"];
      $user_last	 	= $_POST["tfa_user_last"];
      $user_pass		= $_POST["tfa_user_pass"];
      $pass_confirm 	= $_POST["tfa_user_pass_confirm"];
      
      // this is required for username checks
      require_once(ABSPATH . WPINC . '/registration.php');
      
      if(username_exists($user_login)) {
          // Username already registered
          tfa_errors()->add('username_unavailable', __('Username already taken'));
      }
      if(!validate_username($user_login)) {
          // invalid username
          tfa_errors()->add('username_invalid', __('Invalid username'));
      }
      if($user_login == '') {
          // empty username
          tfa_errors()->add('username_empty', __('Please enter a username'));
      }
      if(!is_email($user_email)) {
          //invalid email
          tfa_errors()->add('email_invalid', __('Invalid email'));
      }
      if(email_exists($user_email)) {
          //Email address already registered
          tfa_errors()->add('email_used', __('Email already registered'));
      }
      if($user_pass == '') {
          // passwords do not match
          tfa_errors()->add('password_empty', __('Please enter a password'));
      }
      if($user_pass != $pass_confirm) {
          // passwords do not match
          tfa_errors()->add('password_mismatch', __('Passwords do not match'));
      }
      
      $errors = tfa_errors()->get_error_messages();
      
      // if no errors then cretate user
      if(empty($errors)) {
          
          $new_user_id = wp_insert_user(array(
                  'user_login'		=> $user_login,
                  'user_pass'	 		=> $user_pass,
                  'user_email'		=> $user_email,
                  'first_name'		=> $user_first,
                  'last_name'			=> $user_last,
                  'user_registered'	=> date('Y-m-d H:i:s'),
                  'role'				=> 'subscriber'
              )
          );
          if($new_user_id) {
              // send an email to the admin
              wp_new_user_notification($new_user_id);
              
              // log the new user in
              wp_setcookie($user_login, $user_pass, true);
              wp_set_current_user($new_user_id, $user_login);	
              do_action('wp_login', $user_login);
              
              // send the newly created user to the home page after logging them in
			
				  
				  
              wp_redirect(home_url()); 
				  
				  
				  
				  exit;
          }
          
      }
  
  }
}
add_action('init', 'tfa_add_new_user');

// used for tracking error messages
function tfa_errors(){
    static $wp_error; // global variable handle
    return isset($wp_error) ? $wp_error : ($wp_error = new WP_Error(null, null, null));
}

// displays error messages from form submissions
function tfa_register_messages() {
	if($codes = tfa_errors()->get_error_codes()) {
		echo '<div class="tfa_errors">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = tfa_errors()->get_error_message($code);
		        echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		    }
		echo '</div>';
	}	
}

// displays error messages from form submissions
function tfa_login_messages() {
	if($codes = tfa_errors()->get_error_codes()) {
		echo '<div class="tfa_errors">';
		    // Loop error codes and display errors
		   foreach($codes as $code){
		        $message = tfa_errors()->get_error_message($code);
		        echo '<span class="error"><strong>' . __('Error') . '</strong>: ' . $message . '</span><br/>';
		    }
		echo '</div>';
	}	
}





	function tfa_login_form(){
				
		if(!is_user_logged_in()) {
			ob_start(); 
			?>	
			<form id="tfa_login_form" class="tfa_form" action="" method="POST">
				<fieldset>
					<br>
					<div class="form_in">
						<p>
							<label for="tfa_user_email_log"><?php _e('Email'); ?></label>
							<input name="tfa_user_email_log" id="tfa_user_email_log" class="tfa_user_email_log" type="email"/>
						</p>
						<p>
							<label for="tfa_user_pass_log"><?php _e('Password'); ?></label>
							<input name="tfa_user_pass_log" id="tfa_user_pass_log" class="password" type="password"/>
						</p>
						<p>
							<input type="hidden" name="tfa_csrf" value="<?php echo wp_create_nonce('vicode-csrf'); ?>"/>
							<input type="submit" value="<?php _e('Login'); ?>"/>
						</p>
					</div>
					<div id="login_status_msg_wp"></div>
					<div id="login_status_msg_fb"></div>
				</fieldset>
				
			</form>
			<?php 
			/*
			echo '<pre>';
			print_r( get_userdata(45) );
			print_r( get_user_meta(45) );
			echo '</pre>';
			*/
			return ob_get_clean();
		}
	}
	
	add_shortcode( 'login_form', 'tfa_login_form' );




	function tfa_fb_profile(){
				

	  $all_meta_for_user = get_user_meta( get_current_user_id() );
	  
		$fbUid = $all_meta_for_user['firebase_uid'][0];
		
		$cur_user = wp_get_current_user();
		$cur_user_roles = ( array ) $cur_user->roles;
		
	//	if(!is_user_logged_in()) {
			ob_start(); 
			/*
			echo '<pre>';
			print_r( $cur_user );
			echo '</pre>';
			*/
		if(is_user_logged_in()) {
			if( $cur_user_roles[0] == "patients" ){ ?>
				
				<div class="tfa_form" id="form_wrap" style="opacity:0.3;">
					<div class="elementor-row">
						<div class="elementor-column elementor-col-75">
							<div class="elementor-row">
								<div class="elementor-column elementor-col-33">
									<p>
										<label for="firstName">firstName:</label>
										<input type="text" id="firstName" name="firstName" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="middleName">middleName:</label>
									<input type="text" id="middleName" name="middleName" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="lastName">lastName:</label>
									<input type="text" id="lastName" name="lastName" class="full_width" value="">
									</p>
								</div>
							</div>
							<div class="elementor-row">
								<div class="elementor-column elementor-col-50">
									<p>
									<label for="address1">address1:</label>
									<input type="text" id="address1" name="address1" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-50">
									<p>
									<label for="address2">address2:</label>
									<input type="text" id="address2" name="address2" class="full_width" value="">
									</p>
								</div>
							</div>
							<div class="elementor-row">
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="city">City:</label>
									<input type="text" id="city" name="city" class="full_width" >
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="state">state:</label>
									<input type="text" id="state" name="state" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="zipCode">zipCode:</label>
									<input type="text" id="zipCode" name="zipCode" class="full_width" value="">
									</p>
								</div>
							</div>
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
							<label for="profileImage">Profile Photo upload:</label>
							<input type="file" id="profileImage" name="profileImage" class="full_width upload_image">
							<input type="hidden" id="profileImage_inp" name="profileImage_inp">
							<img src="" class="profileimg_preview upload_image_prev">
							<span id="profilepic_upl_sts"><span>
							</p>
						</div>
					</div>
					<div class="elementor-row">
						<div class="elementor-column elementor-col-25">
							<p>
							<label for="email">Email:</label>
							<input type="email" id="email" name="email" class="full_width" readonly value="<?php echo $cur_user->data->user_email; ?>">
							</p>
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
							<label for="phoneNumber">Phone:</label>
							<input type="text" id="phoneNumber" name="phoneNumber" class="full_width" value="">
							</p>
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
							<label for="dateOfBirth">Date of birth:</label>
							<input type="date" id="dateOfBirth" name="dateOfBirth" class="full_width" value="">
							</p>
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
							<label for="gender">Gender:</label>
							<select id="gender" name="gender">
								<option value="">Select</option>
								<option value="Male">Male</option>
								<option value="Female">Female</option>
							</select>
							</p>
						</div>
					</div>
					<div class="elementor-row">
						<div class="elementor-column elementor-col-75">
						
							<b>Select your location: </b>
							
						<div class="elementor-row">
							<div class="elementor-col-50">
									  <label for="loc_lat">Latitude</label>
									  <input type="text" id="loc_lat" name="loc_lat"
												class="form-control form-control-sm code_text"
												placeholder="#Latitude" value="" readonly>
							</div>
							<div class="elementor-col-50">
									  <label for="loc_lng">Longitude</label>
									  <input type="text" id="loc_lng" name="loc_lng"
												class="form-control form-control-sm code_text"
												placeholder="#Longitude" readonly>
							</div>
						</div>
							<div id="map_canvas" style="width: 99%; height: 300px;"></div>
						 <!--
							  <label for="zoom">Zoom</label>
							  <input type="text" id="zoom" name="zoom" class="form-control form-control-sm code_text"
										placeholder="#Zoom"
										readonly>
						 -->
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
								<label for="enableTouchId">enableTouchId:</label>
								<select id="enableTouchId" name="enableTouchId">
									<option value="false">False</option>
									<option value="true">True</option>
								</select>
								<br>
								<br>
								<label for="groupNumber">groupNumber:</label>
								<input type="text" id="groupNumber" name="groupNumber" class="full_width" value="">
								<br>
								<br>
								<label for="insuranceCompany">insuranceCompany:</label>
								<input type="text" id="insuranceCompany" name="insuranceCompany" class="full_width" value="">
								<input type="hidden" id="rememberUsername" name="rememberUsername" value="" /> 
								<input type="hidden" id="enableTouchId" name="enableTouchId" value="" /> 
								<br>
								<br>
								<button class="wpforms-submit  full_width" id="" onclick="firebase_profile_data_saving()">Save your changes</button>
								
								<div id="profile_update_msg_wp"></div>
							</p>
						</div>
					</div>
				</div>
				
			<?php 
			}else { ?>
				<h4>This user type "<?php echo $cur_user_roles[0]; ?>" not allowed to edit profile data here. </h4>
			<?php 
			}
		}else{ ?>
			<h4>You have to login for this page</h4>
		<?php 
		}
			//  print_r( $cur_user_roles[0] );
			//  print_r( $all_meta_for_user );
			//  print_r( $fbUid );
			?>
			<script>
			
			function uploadImage(firebase) {
			//	alert('uploading ...');
				var returnVal = '';
				const ref = firebase.storage().ref();
				const file = document.querySelector("#profileImage").files[0];
				
				var fileName = "<?= $fbUid ?>-" + file.name;
				if( file.type == "image/png" ){
					fileName = "<?= $fbUid ?>.png";
				}else if( file.type == "image/jpeg" || file.type == "image/jpg" ){
					fileName = "<?= $fbUid ?>.jpg";
				}
				
				const metadata = {
				  contentType: file.type
				};
				const task = ref.child('users/images/'+fileName).put(file, metadata);
				console.log('file: ',file);
				console.log('metadata: ',metadata);
				console.log('task: ',task);
				task
					.on('state_changed', 
						(snapshot) => {
					
						 // Observe state change events such as progress, pause, and resume
						 // Get task progress, including the number of bytes uploaded and the total number of bytes to be uploaded
						 var progress = (snapshot.bytesTransferred / snapshot.totalBytes) * 100;
						 console.log('Upload is ' + progress + '% done');
							document.querySelector("#profilepic_upl_sts").innerHTML = 'Upload is ' + progress + '% done';
						 
						 switch (snapshot.state) {
							case firebase.storage.TaskState.PAUSED: // or 'paused'
							  console.log('Upload is paused');
							  break;
							case firebase.storage.TaskState.RUNNING: // or 'running'
							  console.log('Upload is running');
							  break;
						 }
							
						 snapshot.ref.getDownloadURL();
						},(error) => {
						 // Handle unsuccessful uploads
						}, 
							() => {
						 // Handle successful uploads on complete
						 // For instance, get the download URL: https://firebasestorage.googleapis.com/...
								task.snapshot.ref.getDownloadURL().then((downloadURL) => {
							//	console.log('File available at', downloadURL);
								document.querySelector(".upload_image_prev").src = downloadURL;
								document.getElementById("profileImage_inp").value = downloadURL;
								returnVal = downloadURL;
							});
						}
					);
					return returnVal;
			}
			
			var fbCurUserPath = 'users/<?= $cur_user_roles[0] ?>/<?= $fbUid ?>';
			//	var ref = firebase.database().ref("users/patient/M8LUGOvPc7P5Sl9Yg5wIMoR5aOI3");

			  firebase.database().ref(fbCurUserPath).once('value',   function(snapshot) {
				  
				 snapshot.forEach(function(childSnapshot) {
					var childKey = childSnapshot.key;
					var childData = childSnapshot.val();
				//	console.log(childSnapshot.key + ':',childData);
				 });
			  });


			  firebase.database().ref(fbCurUserPath).on('value',(snap)=>{
					var totalRecord =  snap.numChildren();
				//	console.log("snap.val() : ", snap.val());
					var profileData = snap.val();
					console.log("profileData::: ",profileData);
					document.getElementById("firstName").value 			= 	(profileData.firstName == "undefined") ? '' : profileData.firstName;
					document.getElementById("middleName").value 			= 	(profileData.middleName == "undefined") ? '' : profileData.middleName;
					document.getElementById("lastName").value 			= 	(profileData.lastName == "undefined") ? '' : profileData.lastName;
					document.getElementById("address1").value 			= 	(profileData.address1 == "undefined") ? '' : profileData.address1;
					document.getElementById("address2").value 			= 	(profileData.address2 == "undefined") ? '' : profileData.address2;
					document.getElementById("phoneNumber").value 		= 	(profileData.phoneNumber == "undefined") ? '' : profileData.phoneNumber;
					document.getElementById("dateOfBirth").value 		= 	profileData.dateOfBirth;
				//	document.getElementById("email").value 				= 	(profileData.email == "undefined") ? '' : profileData.email;
					document.getElementById("city").value 					= 	(profileData.city == "undefined") ? '' : profileData.city;
					document.getElementById("state").value 				= 	(profileData.state == "undefined") ? '' : profileData.state;
					document.getElementById("zipCode").value 				= 	(profileData.zipCode == "undefined") ? '' : profileData.zipCode;
					document.getElementById("gender").value 				= 	(profileData.gender == "undefined") ? '' : profileData.gender;
					document.getElementById("enableTouchId").value 		= 	(profileData.enableTouchId == "undefined") ? '' : profileData.enableTouchId;
					document.getElementById("groupNumber").value 		= 	(profileData.groupNumber == "undefined") ? '' : profileData.groupNumber;
					document.getElementById("insuranceCompany").value 	= 	(profileData.insuranceCompany == "undefined") ? '' : profileData.insuranceCompany;
					document.getElementById("loc_lat").value 				= 	(profileData.location.lat == "undefined") ? '' : profileData.location.lat;
					document.getElementById("loc_lng").value 				= 	(profileData.location.lng == "undefined") ? '' : profileData.location.lng;
					document.getElementById("profileImage_inp").value 	= 	(profileData.profileImage == "undefined") ? '' : profileData.profileImage;
					document.querySelector(".upload_image_prev").src 	= 	(profileData.profileImage == "undefined") ? '' : profileData.profileImage;
					
					if( profileData.rememberUsername ){
						document.getElementById("rememberUsername").value 		= 	true;
					}else{
						document.getElementById("rememberUsername").value 		= 	false;
					}
					
					if( profileData.enableTouchId ){
						document.getElementById("enableTouchId").value 		= 	true;
					}else{
						document.getElementById("enableTouchId").value 		= 	false;
					}
					
					
					document.getElementById("form_wrap").style.opacity = "1";
				//	initialize(profileData.location.lat, profileData.location.lng);
				//	document.querySelector(".upload_image_prev").src = url;
				//	console.log('profileData.dateOfBirth:',profileData.dateOfBirth);
				//	console.log("city : ", profileData.city);
			  });
			  
			  function fb_data_saving(key,val){
				  
				 var ret = firebase.database().ref(fbCurUserPath + '/'+key).set(val,(error)=>{
																														if (error) {
																															 ret = error;
																														  } else {
																															 ret = key + 'Data saved successfully!';
																														  }
																														}
																									).then(function(result){
																										console.log('result:',result);
																									});
					console.log('ret:',ret);
			  }
			  
			  function firebase_profile_data_saving(){
				  document.getElementById("form_wrap").style.opacity = "0.4";

				//  $('#profile_update_msg_wp').html('<div class="loader"></div>');	
				  document.querySelector("#profile_update_msg_wp").innerHTML = '<div class="loader"></div>';
				  /*
				  fb_data_saving('firstName', document.getElementById("firstName").value);
				  fb_data_saving('middleName', document.getElementById("middleName").value);
				  fb_data_saving('lastName', document.getElementById("lastName").value);
				  fb_data_saving('address1', document.getElementById("address1").value);
				  fb_data_saving('address2', document.getElementById("address2").value);
				  fb_data_saving('phoneNumber', document.getElementById("phoneNumber").value);
				  fb_data_saving('dateOfBirth', document.getElementById("dateOfBirth").value);
				  fb_data_saving('city', document.getElementById("city").value);
				  fb_data_saving('state', document.getElementById("state").value);
				  fb_data_saving('zipCode', document.getElementById("zipCode").value);
				  fb_data_saving('groupNumber', document.getElementById("groupNumber").value);
				  fb_data_saving('enableTouchId', document.getElementById("enableTouchId").value);
				  fb_data_saving('insuranceCompany', document.getElementById("insuranceCompany").value);
				  fb_data_saving('gender', document.getElementById("gender").value);
					*/
				//	/*
					firebase.database().ref(fbCurUserPath).set({
						
						firstName:    		document.getElementById("firstName").value,
						middleName:   		document.getElementById("middleName").value,
						lastName:     		document.getElementById("lastName").value,
						address1:     		document.getElementById("address1").value,
						address2:     		document.getElementById("address2").value,
						phoneNumber:  		document.getElementById("phoneNumber").value,
						dateOfBirth:  		document.getElementById("dateOfBirth").value,
					//	email:        		document.getElementById("email").value,
						city:         		document.getElementById("city").value,
						state:        		document.getElementById("state").value,
						zipCode:      		document.getElementById("zipCode").value,
						groupNumber:  		document.getElementById("groupNumber").value,
						enableTouchId:		document.getElementById("enableTouchId").value,
						insuranceCompany: document.getElementById("insuranceCompany").value,
						gender:      		document.getElementById("gender").value,
						profileImage:     document.getElementById("profileImage_inp").value,
						location:			{
												
													lat: document.getElementById("loc_lat").value,
													lng: document.getElementById("loc_lng").value
													/*
													lat: 765765,
													lng: 9087089	
													*/
												}
					}).then(function(){
						
						document.getElementById("form_wrap").style.opacity = "1";
						document.getElementById('profile_update_msg_wp').innerHTML = '<p style="font-weight: bold; color: green;">Profile data updated successfully.</p>';
					});
					
					uploadImage(firebase);
				//	*/
				}
				
			/* ================== Google Map ================== */
			/* ================= Google Map ================= */
			var geocoder;
			var map;
			var marker;
			var defZoom = 8;
		//	var defLat = 40.7253482997065;
		//	var defLon = -74.8773728203125;

		//	$('#zoom').val(defZoom);
		//	$('#latitude').val(defLat);
		//	$('#longitude').val(defLon);

			function handleEvent(event) {
				 console.log(map.getZoom());
				 document.getElementById('loc_lat').value = event.latLng.lat();
				 document.getElementById('loc_lng').value = event.latLng.lng();
			}

			function initialize(defLat = 40.7253482997065,defLon = -74.8773728203125) {

			  firebase.database().ref(fbCurUserPath).on('value',(snap)=>{
					var totalRecord =  snap.numChildren();
				//	console.log("snap.val() : ", snap.val());
					var profileData = snap.val();
					
					defLat = profileData.location.lat;
					defLon = profileData.location.lng;
					
					 map = new google.maps.Map(
								document.getElementById("map_canvas"), {
						  center: new google.maps.LatLng(defLat, defLon),
						  zoom: defZoom,
						  mapTypeId: google.maps.MapTypeId.ROADMAP
					 });

					 // =============== Get zoom data
					 google.maps.event.addListener(map, 'zoom_changed', function () {
						//  $('#zoom').val(map.getZoom());
					 });

					 // =============== Set default marker
					 marker = new google.maps.Marker({
						  position: new google.maps.LatLng(defLat, defLon),
						  draggable: true,
						  //    icon: {
						  //        url: base_url + 'dashboard/images/Ripple-1.6s-126px.gif',
						  //        anchor: new google.maps.Point(63, 63)
						  //    },
						  map: map
					 });


					 // =============== Marker on Drag
					 marker.addListener('drag', handleEvent);
					 marker.addListener('dragend', handleEvent);
					 //    console.log(map.getZoom());

					 // =============== Marker setup onclick
					 google.maps.event.addListener(map, "click", function (e) {

						  latLng = e.latLng;
						  
						  document.getElementById("loc_lat").value = e.latLng.lat();
						  document.getElementById("loc_lng").value = e.latLng.lng();
						  

						  // if marker exists and has a .setMap method, hide it
						  if (marker && marker.setMap) {
								marker.setMap(null);
						  }
						  marker = new google.maps.Marker({
								position: latLng,
							//	radius: 100,
								draggable: true,
								//     icon: {
								//         url: base_url + 'dashboard/images/Ripple-1.6s-126px.gif',
								//         anchor: new google.maps.Point(63, 63)
								//     },
								map: map
						  });
						  marker.addListener('drag', handleEvent);
						  marker.addListener('dragend', handleEvent);
						  map.panTo(latLng);

					 });
				});

			}
			/* ==================/Google Map ================== */
			
			</script>
			<script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDmnI7-QHCE5Kw-tq8FmfKaKKDGRZTemOE&callback=initialize"></script>
			<?php 
			/*
			echo '<pre>';
			print_r( get_userdata(45) );
			print_r( get_user_meta(45) );
			echo '</pre>';
			*/
			return ob_get_clean();
	//	}
	}
	
	add_shortcode( 'wp_firebase_prifile', 'tfa_fb_profile' );


