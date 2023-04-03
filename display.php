<!DOCTYPE html>
<html>
 <head>
	<title>Display Image</title>
	<style type="text/css">
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            overflow: hidden; /* Hide scrollbars */
        }

		img {
            width: 100%;
            height: 100vh;
            object-fit: cover;
		}
	</style>
 </head>
 <body>
	<?php
    // Read the contents of the text file
		$file_contents = file_get_contents("image.txt");

		// Split the contents into an array using a delimiter
		$file_info = explode("|", $file_contents);

		// Get the filename and path
		$file_name = trim((string) $file_info[0]);
		$file_signature = trim((string) $file_info[1]);
    
    if (substr($file_name,-3) == "mp4") {
      // Display video
      echo " <video controls autoplay loop>\n";
      echo "  <source src=\"Media\\$file_name\" type=\"video/mp4\">\n";
      echo "  Your browser does not support the video tag.\n";
      echo " </video>\n";
    } else {
      // Display the image
      echo "<img src='Media/{$file_name}' alt='{$file_name}'>";
    }
      
    
    ?>
</body>
<script src="https://cdn.jsdelivr.net/npm/md5-js-tools@1.0.2/lib/md5.min.js"></script>
<script>

function checkForChanges() {
  // Make a request to the image.txt file on localhost:8080
  fetch('image.txt')
    .then(response => response.text())
    .then(data => {
      // Calculate the data signature using a hash function (e.g. SHA-256)
      const dataSignature = MD5.generate(data);
      // Check if the data signature has changed since the last check
      if (dataSignature !== localStorage.getItem('lastDataSignature')) {
        // If the data signature has changed, reload the page
        location.reload();
      } else {
        // If the data signature hasn't changed, schedule the next check
        setTimeout(checkForChanges, 5000);
      }
      // Store the current data signature for the next check
      localStorage.setItem('lastDataSignature', dataSignature);
    })
    .catch(error => {
      // Handle any errors that occur during the fetch
      console.error(error);
      // Schedule the next check
      setTimeout(checkForChanges, 5000);
    });
}

// Start checking for changes
checkForChanges();

</script>
</html>