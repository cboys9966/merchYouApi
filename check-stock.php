<div class="wc_merch_container">

	<form action="" method="POST">
		<input type="text" class="sku-field" name="sku-number" value="" placeholder="Please enter sku number">
		<input type="submit" name="sku-submit" class="button button-primary button-large">
	</form>

<?php 

	if(isset($_POST['sku-submit']))
	{
		
	 	$sku_number = $_POST['sku-number']; 

	 	$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, "https://textile.merchyou.com/api/v1/store-check/".$sku_number);
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
                
				echo '<b>The Product Stock = </b>'.$results->stock;

			}

		}

		
	}

		curl_close ($ch);

?> 
</div>



