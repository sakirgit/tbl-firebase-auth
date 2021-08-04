<?php 

// user registration login form
function fn_tbl_app_reg_form() {
 
	// only show the registration form to non-logged-in members
	if(!is_user_logged_in()) {
 
		// check if registration is enabled
		$registration_enabled = get_option('users_can_register');
 
		// if enabled
		if($registration_enabled) {
			$output = tbl_app_fb_reg_fields();
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
add_shortcode('app_register_form', 'fn_tbl_app_reg_form'); 

// registration form fields
function tbl_app_fb_reg_fields() {
	
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



	function tfa_app_login_form(){
				
		if(!is_user_logged_in()) {
			ob_start(); 
			?>	
			<form id="tfa_login_form" class="tfa_form" action="" method="POST">
				<fieldset>
					<br>
					<div class="form_in">
						<p>
							<label for="tfa_user_email_log"><?php _e('Email'); ?></label>
							<input name="tfa_user_email_log" id="tfa_user_email_log" class="tfa_user_email_log" type="email" value="" />
						</p>
						<p>
							<label for="tfa_user_pass_log"><?php _e('Password'); ?></label>
							<input name="tfa_user_pass_log" id="tfa_user_pass_log" class="password" type="password" value="" />
						</p>
						<p>
							<input type="hidden" name="tfa_csrf" value="<?php echo wp_create_nonce('vicode-csrf'); ?>"/>
							<input type="submit" value="<?php _e('Login'); ?>"/>
						</p>
					</div>
					<div id="login_status_msg_fb"></div>
				<a href="<?php home_url("/"); ?>/email-collection-to-reset-password/" style="float:right;">Forgot your password ?</a>
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
	add_shortcode( 'app_login_form', 'tfa_app_login_form' );





function tfa_app_ajax_form_scripts(){
	global $post;
	if ( has_shortcode( $post->post_content, 'app_register_form' ) ) {
?>
<script type="text/javascript">
jQuery(document).ready(function($){
    "use strict";
			 
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
				
				/* ========== User data add to Firebase Database ========== */
				
				firebase.database().ref('userType/'+ fbUid +'/userType').set(userType);
				
				firebase.database().ref('users/'+ userType +'/' + fbUid).set({
					firstName: document.getElementsByName("tfa_user_first")[0].value,
					lastName: document.getElementsByName("tfa_user_last")[0].value,
					email: user_email
				}).then(function(result){
					
						$('#reg_status_msg_wp').html('<p style="color:green;"><b><i>('+user_email+')</i> Registration Success! Now Redirecting to Login, Please wait.</b></p>');	
						$('form .form_in').hide('slow');
						window.location.href = "<?php echo home_url('/registration-login-app?msg=Registration-Success!'); ?>";
				});
				
				// Ajaxify the Form
				var data = {
					'action': 'tfa_custom_reg_ajax',
					'inputFieldValues':   $('form#tfa_registration_form').serialize(),
					'fbUid': fbUid
				};
				
			}).catch(function(error){
				console.log('firebase_res e:',error);
				$('#reg_status_msg_fb').html('<p style="color:red;"><b><i>('+user_email+')</i> Firebase Registration error.</b><br>'+error+'</p>');
			});
		  
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
			
			var user_email = document.getElementsByName("tfa_user_email_log")[0].value;
			var user_pass = document.getElementsByName("tfa_user_pass_log")[0].value;

			firebase.auth().signInWithEmailAndPassword(user_email,user_pass).then(function(res){	
				console.log('firebase_res:',res);	
				
				$('#login_status_msg_fb').html('<p style="color:green;"><b><i>('+user_email+')</i> Firebase Login success.</b></p>');	
				
				var userID = res.user.uid;
				
				var fbCurUserType = 'userType/'+ userID;
				//	var ref = firebase.database().ref("users/patient/M8LUGOvPc7P5Sl9Yg5wIMoR5aOI3");
				
				firebase.database().ref(fbCurUserType).on('value',(snap)=>{
					var userTypeData = snap.val();
					console.log("userTypeData:",userTypeData);
					

					var loginData = {
						loginStatus: "success",
						loginEmail: user_email,
						userID: userID,
						userType: userTypeData.userType
					};
					loginData = JSON.stringify(loginData);
					sessionStorage.setItem("firebase_login", loginData);
					
					console.log("firebase_login:",JSON.parse(sessionStorage.getItem("firebase_login")));
					
					window.location.href = "<?php echo home_url('/app-profile'); ?>";
				});
				
				
			}).catch(function(error){
				console.log('firebase_res e:',error);
				$('#login_status_msg_fb').html('<p style="color:red;"><b> '+ error.message +' </b></p>');
			});
    });
	
	
	
}(jQuery));    
</script>
	<?php } 
}
add_action('wp_footer','tfa_app_ajax_form_scripts', 100);







	function tfa_fb_app_profile(){
				

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
			?>
				<p style="border:  1px solid #ddd;padding: 5px 10px;">"<b>*</b>" is required fields.<span style="float: right;">Usertype<b>: <span id="usertype_lbl"></span></b> &nbsp; | &nbsp; <a onclick="fb_auth_logout()" style="cursor: pointer;"><b>Logout</b></a></span></p>
				<div class="tfa_form" id="form_wrap" style="opacity:0.3;">
					<div class="elementor-row">
						<div class="elementor-column elementor-col-75">
							<div class="elementor-row">
								<div class="elementor-column elementor-col-33">
									<p>
										<label for="firstName">First Name: <b>*</b></label>
										<input type="text" id="firstName" name="firstName" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="middleName">Middle Name:</label>
									<input type="text" id="middleName" name="middleName" class="full_width" value="">
									</p>
								</div>
								<div class="elementor-column elementor-col-33">
									<p>
									<label for="lastName">Last Name: <b>*</b></label>
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
							<img src="" class="profileimg_preview upload_image_prev" alt="(Profie Pic)">
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
							<label for="phoneNumber">Phone: <b>*</b></label>
							<input type="text" id="phoneNumber" name="phoneNumber" class="full_width" value="">
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
					<span id="error_show" style="color: red;"></span>
					<hr>
					
					<div class="elementor-row ">
						<div class="elementor-row data_for_patient">
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="groupNumber">groupNumber:</label>
								<input type="text" id="groupNumber" name="groupNumber" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
									<label for="insuranceCompany">insuranceCompany:</label>
									<input type="text" id="insuranceCompany" name="insuranceCompany" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
									<label for="enableTouchId">enableTouchId:</label>
									<select id="enableTouchId" name="enableTouchId">
										<option value="false">False</option>
										<option value="true">True</option>
									</select>
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="dateOfBirth">Date of birth: </label>
								<input type="date" id="dateOfBirth" name="dateOfBirth" class="full_width" value="">
								</p>
							</div>
						</div>
						<div class="elementor-row data_for_provider">
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="npiNumber">NIP Number:</label>
								<input type="number" id="npiNumber" name="npiNumber" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
									<label for="serviceType">Service Type:</label>
									<input type="text" id="serviceType" name="serviceType" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
									<label for="speciality">Speciality:</label>
									<input type="text" id="speciality" name="speciality" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
									<label for="taxonomy">Taxonomy:</label>
									<input type="text" id="taxonomy" name="taxonomy" class="full_width" value="">
								</p>
							</div>
						</div>
					</div>
					<div class="elementor-row ">
						<div class="elementor-row data_for_patient">
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="insurancePlan">Insurance Plan:</label>
								<input type="text" id="insurancePlan" name="insurancePlan" class="full_width" value="">
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="policyNumber">Policy Number:</label>
								<input type="text" id="policyNumber" name="policyNumber" class="full_width" value="">
								</p>
							</div>
						</div>
						<div class="elementor-row data_for_provider">
							<div class="elementor-column elementor-col-50">
								<p>
								<label for="howDoYouHearAboutUs">How Do You Hear About Us:</label>
								<textarea id="howDoYouHearAboutUs" name="howDoYouHearAboutUs" class="full_width" rows="5"></textarea>
								</p>
							</div>
							<div class="elementor-column elementor-col-25">
								<p>
								<label for="officeNumber">Office Number:</label>
								<input type="text" id="officeNumber" name="officeNumber" class="full_width" value="">
								</p>
								<p>
								<label for="numberOfProvinceInClinic">Number Of Province In Clinic:</label>
								<input type="text" id="numberOfProvinceInClinic" name="numberOfProvinceInClinic" class="full_width" value="">
								</p>
							</div>
						</div>
					</div>
					<div class="elementor-row data_for_provider">
						<div class="elementor-column elementor-col-33">
							<p>
							<label for="prefix">prefix:</label>
							<input type="text" id="prefix" name="prefix" class="full_width" value="">
							</p>
						</div>

						<div class="elementor-column elementor-col-33">
							<p>
							<label for="suffix">suffix:</label>
							<input type="text" id="suffix" name="suffix" class="full_width" value="">
							</p>
						</div>
					</div>
					<div class="elementor-row">
						<div class="elementor-column elementor-col-75 ">
							<span class="data_for_patient">
							
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
							</span>
						</div>
						<div class="elementor-column elementor-col-25">
							<p>
								<br>
								<button class="wpforms-submit  full_width" id="" onclick="uploadImage()">Save your changes</button>
								
								<input type="hidden" id="rememberUsername" name="rememberUsername" value="" /> 
								<input type="hidden" id="enableTouchId" name="enableTouchId" value="" /> 
								
								<div id="profile_update_msg_wp"></div>
							</p>
						</div>
					</div>
				</div>

			<?php 
			
		
			//  print_r( $cur_user_roles[0] );
			//  print_r( $all_meta_for_user );
			//  print_r( $fbUid );
			?>
			<script>
			
			var fbLoggedIn = JSON.parse(sessionStorage.getItem("firebase_login"));
			var fbUid = fbLoggedIn.userID;
			var loginEmail = fbLoggedIn.loginEmail;
			var fbUserType = fbLoggedIn.userType;
			
			console.log("fbLoggedIn:",fbLoggedIn);
			
		  function firebase_profile_data_saving(){
			  document.getElementById("form_wrap").style.opacity = "0.4";

			//  $('#profile_update_msg_wp').html('<div class="loader"></div>');	
				document.querySelector("#profile_update_msg_wp").innerHTML = '<div class="loader"></div>';
				
				var data2Set = {};
				data2Set.firstName = document.getElementById("firstName").value;
				data2Set.middleName = document.getElementById("middleName").value;
				data2Set.lastName = document.getElementById("lastName").value;
				data2Set.email = loginEmail;
				data2Set.address1 = document.getElementById("address1").value;
				data2Set.address2 = document.getElementById("address2").value;
				data2Set.phoneNumber = document.getElementById("phoneNumber").value;
				data2Set.city = document.getElementById("city").value;
				data2Set.state = document.getElementById("state").value;
				data2Set.zipCode = document.getElementById("zipCode").value;
				data2Set.gender = document.getElementById("gender").value;
				data2Set.profileImage = document.getElementById("profileImage_inp").value;
				data2Set.uId = fbUid;
				

				if( fbUserType == "Patient" || fbUserType == "patient" ){
					
					data2Set.dateOfBirth = document.getElementById("dateOfBirth").value;
					data2Set.groupNumber = document.getElementById("groupNumber").value;
					data2Set.insuranceCompany = document.getElementById("insuranceCompany").value;
					data2Set.insurancePlan = document.getElementById("insurancePlan").value;
					data2Set.policyNumber = document.getElementById("policyNumber").value;
					data2Set.rememberUsername = document.getElementById("rememberUsername").value;
					data2Set.enableTouchId = document.getElementById("enableTouchId").value;
					data2Set.location = {
								
									lat: document.getElementById("loc_lat").value,
									lng: document.getElementById("loc_lng").value
									/*
									lat: 765765,
									lng: 9087089	
									*/
								}
					
				}else if( fbUserType == "Provider" || fbUserType == "provider" ){
					
					data2Set.npiNumber = document.getElementById("npiNumber").value;
					data2Set.serviceType = document.getElementById("serviceType").value;
					data2Set.speciality = document.getElementById("speciality").value;
					data2Set.taxonomy = document.getElementById("taxonomy").value;
					data2Set.howDoYouHearAboutUs = document.getElementById("howDoYouHearAboutUs").value;
					data2Set.officeNumber = document.getElementById("officeNumber").value;
					data2Set.numberOfProvinceInClinic = document.getElementById("numberOfProvinceInClinic").value;
					data2Set.prefix = document.getElementById("prefix").value;
					data2Set.suffix = document.getElementById("suffix").value;
				}
				
				firebase.database().ref(fbCurUserPath).set(data2Set).then(function(){
					
					document.getElementById("form_wrap").style.opacity = "1";
					document.getElementById('profile_update_msg_wp').innerHTML = '<p style="font-weight: bold; color: green;">Profile data updated successfully.</p>';
				});
				
			//	*/
			}
				
			function uploadImage() {
			//	alert('uploading ...');
			
			
				var firstName = document.getElementById("firstName").value;
				var lastName = document.getElementById("lastName").value;
				var phoneNumber = document.getElementById("phoneNumber").value;
				
				if( firstName != '' && lastName != '' && phoneNumber != '' ){
						

					document.getElementById("error_show").innerHTML = "";
					document.getElementById("firstName").style.borderColor = 
					document.getElementById("lastName").style.borderColor = 
					document.getElementById("phoneNumber").style.borderColor = "#eaeaea";
					
						
						
					var returnVal = '';
					const ref = firebase.storage().ref();
					const file = document.querySelector("#profileImage").files[0];
					console.log('document.getElementById("profileImage").files.length',document.getElementById("profileImage").files.length);
					if(document.getElementById("profileImage").files.length > 0 ){
							
						var fileName = fbUid + "-" + file.name;
						if( file.type == "image/png" ){
							fileName = fbUid + ".png";
						}else if( file.type == "image/jpeg" || file.type == "image/jpg" ){
							fileName = fbUid + ".jpg";
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
										
										firebase_profile_data_saving();
									});
								}
							);
							return returnVal;
					}else{
						
						firebase_profile_data_saving();
					}
				}else{
				//	console.log("kkkkkkkkkkkkkkkkkkkkkkkk");
					document.getElementById("error_show").innerHTML = "<b>First Name</b> AND <b>Last Name</b> AND <b>Phone number</b> must fill up.";
					if( firstName == '' ){
						 document.getElementById("firstName").style.borderColor = "red";
					}
					
					if( lastName == '' ){
						 document.getElementById("lastName").style.borderColor = "red";
					}
					
					if( phoneNumber == '' ){
						 document.getElementById("phoneNumber").style.borderColor = "red";
					}
					
					
				}
			}
			
			if( fbUserType == "Patient" || fbUserType == "patient" ){
				
				
				 const providerInputs = document.getElementsByClassName("data_for_provider");
				 while(providerInputs.length > 0){
					  providerInputs[0].remove(providerInputs[0]);
				 }
				
			}else if( fbUserType == "Provider" || fbUserType == "provider" ){
				
				 const patientInputs = document.getElementsByClassName("data_for_patient");
				 while(patientInputs.length > 0){
					  patientInputs[0].remove(patientInputs[0]);
				 }
			}
			
			var fbCurUserPath = 'users/'+ fbUserType.toLowerCase() +'/' + fbUid;
			//	var ref = firebase.database().ref("users/patient/M8LUGOvPc7P5Sl9Yg5wIMoR5aOI3");

			  firebase.database().ref(fbCurUserPath).once('value',   function(snapshot) {
				  
				 snapshot.forEach(function(childSnapshot){	
					var childKey = childSnapshot.key;
					var childData = childSnapshot.val();
				//	console.log(childSnapshot.key + ':',childData);
				 });
			  });
				
				
			  firebase.database().ref(fbCurUserPath).on('value',(snap)=>{
					var totalRecord =  snap.numChildren();
				//	console.log("snap.val() : ", snap.val());
					var profileData = snap.val();
				//	console.log("profileData::: ",profileData);
					if( profileData.firstName ){ document.getElementById("firstName").value 			=  profileData.firstName }
					if( profileData.middleName ){ document.getElementById("middleName").value 			=  profileData.middleName }
					if( profileData.lastName ){ document.getElementById("lastName").value 				=  profileData.lastName }
					if( profileData.address1 ){ document.getElementById("address1").value 				=  profileData.address1 }
					if( profileData.address2 ){ document.getElementById("address2").value 				=  profileData.address2 }
					if( profileData.phoneNumber ){ document.getElementById("phoneNumber").value 		=  profileData.phoneNumber }
					document.getElementById("email").value 														= 	fbLoggedIn.loginEmail;
					if( profileData.city ){ document.getElementById("city").value 							=  profileData.city }
					if( profileData.state ){ document.getElementById("state").value 						=  profileData.state }
					if( profileData.zipCode ){ document.getElementById("zipCode").value 					=  profileData.zipCode }
					if( profileData.gender ){ document.getElementById("gender").value 					=  profileData.gender }
				//	console.log("profileData.profileImage",profileData.profileImage);
					if( profileData.profileImage ){
																document.getElementById("profileImage_inp").value =  profileData.profileImage; 
															//	if( isURL(profileData.profileImage) ){
																	document.querySelector(".upload_image_prev").src =  profileData.profileImage; 
															//	}
															}
					
					if( fbUserType == "Patient" || fbUserType == "patient" ){
						
						if( profileData.dateOfBirth ){ document.getElementById("dateOfBirth").value 	=  profileData.dateOfBirth }
						if( profileData.enableTouchId ){ document.getElementById("enableTouchId").value 	=  profileData.enableTouchId }
						if( profileData.groupNumber ){ document.getElementById("groupNumber").value 		=  profileData.groupNumber }
						if( profileData.insuranceCompany ){ document.getElementById("insuranceCompany").value =  profileData.insuranceCompany }
						if( profileData.policyNumber ){ document.getElementById("policyNumber").value =  profileData.policyNumber }
						if( profileData.insurancePlan ){ document.getElementById("insurancePlan").value =  profileData.insurancePlan }
						if( profileData.location){
							if( profileData.location.lat ){ document.getElementById("loc_lat").value 		=  profileData.location.lat }
							if( profileData.location.lng ){ document.getElementById("loc_lng").value 		=  profileData.location.lng }
						}
						
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
						
					}else if( fbUserType == "Provider" || fbUserType == "provider" ){
						
						if( profileData.npiNumber ){ document.getElementById("npiNumber").value 		=  profileData.npiNumber }
						if( profileData.serviceType ){ document.getElementById("serviceType").value 	=  profileData.serviceType }
						if( profileData.speciality ){ document.getElementById("speciality").value 		=  profileData.speciality }
						if( profileData.howDoYouHearAboutUs ){ document.getElementById("howDoYouHearAboutUs").value =  profileData.howDoYouHearAboutUs }
						if( profileData.officeNumber ){ document.getElementById("officeNumber").value =  profileData.officeNumber }
						if( profileData.taxonomy ){ document.getElementById("taxonomy").value =  profileData.taxonomy }
						if( profileData.prefix ){ document.getElementById("prefix").value =  profileData.prefix }
						if( profileData.suffix ){ document.getElementById("suffix").value =  profileData.suffix }
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
				//	console.log('ret:',ret);
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
				// console.log(map.getZoom());
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
	
	add_shortcode( 'wp_firebase_app_profile', 'tfa_fb_app_profile' );




	function fn_fb_forgot_pass_get_email(){
		ob_start();
		?>
			<label for="emailid">Your email address to sent you password reset link:
			<input type="email" id="emailid" name="emailid" class="full_width" value="">
			</label>
			<br> <br>
			<button onclick="firebase_sendPasswordResetEmail()"> Submit </button>
			<script>
			
			function firebase_sendPasswordResetEmail(){
				var emailid = document.getElementById("emailid").value;
				firebase.auth().sendPasswordResetEmail(emailid).then(() => {
						 console.log("Password reset email sent!");
					  })
					  .catch((error) => {
						 var errorCode = error.code;
						 var errorMessage = error.message;
						 // ..
					  });
			}
			</script>
		<?php 
			return ob_get_clean();
	}

	add_shortcode( 'fb_forgot_pass_get_email', 'fn_fb_forgot_pass_get_email' );


function tfa_app_profile_js(){
	global $post;
	if ( has_shortcode( $post->post_content, 'wp_firebase_app_profile' ) ) {
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
		
		
		var body = document.body;
		if (sessionStorage.length == 0) {
			body.classList.add("fb_auth_loggedout");
		}else{
			body.classList.add("fb_auth_loggedin");
			
			var fbLoggedIn = JSON.parse(sessionStorage.getItem("firebase_login"));
			var fbUid = fbLoggedIn.userID;
			var loginEmail = fbLoggedIn.loginEmail;
			var fbUserType = fbLoggedIn.userType;
			
			document.getElementById("usertype_lbl").appendChild(document.createTextNode(fbUserType));
		}
		
}(jQuery));    

function fb_auth_logout(){
	sessionStorage.clear();
	window.location.href = "<?php echo home_url('/'); ?>";
}

</script>
	<?php } 
}
add_action('wp_footer','tfa_app_profile_js', 100);


function tfa_app_profile_js_4_head(){
?>
<script type="text/javascript">
</script>
	<?php 
}
add_action('wp_head','tfa_app_profile_js_4_head', 100);

