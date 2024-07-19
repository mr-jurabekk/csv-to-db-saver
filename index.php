<?php
include 'upload.php';
?>
<!DOCTYPE html>
<html>

<head>
  <title>Upload XLS File</title>
</head>

<body>
  <form action="upload.php" method="post" enctype="multipart/form-data">
    Select XLS file to upload:
    <input type="file" name="fileToUpload" id="fileToUpload" accept=".csv">
    <input type="submit" value="Upload File" name="submit">
  </form>



</body>

</html>