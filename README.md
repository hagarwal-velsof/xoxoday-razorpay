1. Run Migration command.
		php artisan migrate
	
	It will create following table in database
		->xorazorpay_payouts
		->xorazorpay_contacts
		->xorazorpay_requests

2. Then the publish command to copy the required files to root folder
		php artisan vendor:publish  --tag="razorpay_files"
	
	This will make copy required job and config files to the root folder

3. Refer code from the sample.php file and execute the functionality of the file
	