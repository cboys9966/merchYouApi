<div class="wc_status_container">

	<form action="" method="POST">
		<input type="text" class="order-number" name="order-number" value="" placeholder="Please enter order number">
		<input type="submit" name="status-submit" class="button button-primary button-large">
	</form>

<?php 

if(isset($_POST['status-submit']))
{

 	$order_number = $_POST['order-number']; 

 	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "https://textile.merchyou.com/api/v1/multidesign-order/state/".$order_number);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");


	$headers = array();
	$headers[] = "Api-Key: b1563974-5756-4d91-a7ca-58c2bf734a1c";
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

	$result = curl_exec($ch);

	if (curl_errno($ch)) 
	{
	    echo 'Error:' . curl_error($ch);
	}
	else 
	{
			
    	$results = json_decode($result);
           
            //print_r($results);

    	if($results->error != '')
		{
			echo '<b> Something Is Wrong!.Please Try Again </b>';
		}
		else
		{
			echo '<b> Delivery Date date of your order is = </b>'.$results->deliveryDate;
		}

	}

	curl_close ($ch);

}

?> 
</div>


