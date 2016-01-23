<?php

session_start();

require 'vendor/autoload.php';

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\Common\Credentials;


//directory name in the s3 bucket that can be unique for any customer
$s3dir = $customer_id = "user_1";

//S3 & accesso S3:
$bucket = '<Bucket Name>';
$RoleArn = '<Role Arn>';

$auth = array(
    'key'		=> '<ACCESS_KEY>', // AccessKey
    'secret'	=> '<SecretKey>' //SecretKey

);


$sts = StsClient::factory($auth);

//Let's define the personalized policy for the user(Customer that use the service):
$Policy = '{
					"Version": "2012-10-17",
					"Statement": [
						{
							"Sid": "AllowAllS3ActionsInUserFolder",
							"Effect": "Allow",
							"Action": [
								"s3:GetObject"
							],
							"Resource": [
								"arn:aws:s3:::'.$bucket.'/'.$s3dir.'/*"
							]
						}
					]
				}';

$result = $sts->assumeRole(array(
    // RoleArn is required
    'RoleArn' => $RoleArn,
    // RoleSessionName is required
    'RoleSessionName' => session_id(), //customer session ID so the generated credentials are valid only for the session
    'Policy' => $Policy,
    'DurationSeconds' => 3600, //Time in seconds that temporary credentials are valid
    //'ExternalId' => '',
    //'SerialNumber' => 'string',
    //'TokenCode' => 'string',
));

$credentials = $sts->createCredentials($result);
?>


<html>
<head>
	<script src="js/aws-s3-sdk-2.2.11.min.js" ></script>
	<script>
		function S3download(filepath){
			
		   /* AWS Configuration: */
			ACCESS_KEY = '<?php echo $credentials->getAccessKeyId();?>';
			SECRET_ACCESS_KEY = '<?php echo $credentials->getSecretKey();?>';
			SESSION_TOKEN = '<?php echo $credentials->getSecurityToken();?>';
			
			BucketName = '<?php echo $bucket; ?>';
			//~ amazonSource="test/data_seconddir.txt";
			//~ amazonSource="test/logo_bluservice.gif";
			FileName=filepath;
			//~ amazonSource="test/Professional Node.js.pdf";
			
			//Create Client S3 object:
			var s3 = new AWS.S3({
				params: {
					Bucket: BucketName,
					signatureVersion: 'v4'
				},
				accessKeyId: ACCESS_KEY,
				secretAccessKey: SECRET_ACCESS_KEY,
				sessionToken: SESSION_TOKEN
			});
			
			//Params for the URL signature 
			var SignatureParams = {
						  Bucket: BucketName,
						  Key: FileName,
						  ResponseContentType: 'application/octet-stream',
						  ResponseContentDisposition: 'attachment;'
						};
			
			//Generate signed URL with the token and the signature
			var urlSign = s3.getSignedUrl('getObject',SignatureParams);
			
			//Let's download the object direct from the S3:
			window.location.href = urlSign;
		}
	</script>
</head>
<body>
	
	
	<?php
	
	echo "<p>S3 directory is: <b>".$s3dir."</b></p>";
	
	/*This part here is only for ilustration */
	$s3 = S3Client::factory(array('credentials' => $credentials));

	echo "<h2>S3 List of Objects:</h2>";
	
	$iterator = $s3->getIterator('ListObjects', array(
		'Bucket' => $bucket
	));

	foreach ($iterator as $object) {
		if(strpos($object['Key'],$user_s3dir."/") === 0){
			echo "<br/><button onclick='S3download(\"".$object['Key']."\")'>".$object['Key']."</button>\n";
		}else{
			echo "<br/><button onclick='S3download(\"".$object['Key']."\")'>".$object['Key']."</button> <small style='color:red;'>Customer doesn't have access on this file</small>\n";
		}
	}
	
	
	
	?>
	
</body>
</html>


