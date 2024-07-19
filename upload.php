<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bizkim";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $target_dir = "assets/file/";   //file direction to save
  $target_file = $target_dir . basename($_FILES["fileToUpload"]["name"]);
  $uploadOk = 1;
  $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

  if (file_exists($target_file)) {
    // echo "Sorry, file already exists.";
    // $uploadOk = 0;
  }

  if ($_FILES["fileToUpload"]["size"] > 5000000) {
    echo "Sorry, your file is too large.";
    $uploadOk = 0;
  }
  $err = '';
  $message = '';
  $quantity = '';
  $allowedTypes = array("csv");
  if (!in_array($fileType, $allowedTypes)) {
    echo "Sorry, only  CSV file is allowed.";
    $uploadOk = 0;
  }

  $filePath = $_FILES['fileToUpload']['tmp_name'];


  if ($uploadOk == 0) {
    echo "Sorry, your file was not uploaded.";
  } else {
    if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {

      $filePath = $target_file;
      if (($handle = fopen($filePath, "r")) !== FALSE) {
        $data = [];

        while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
          foreach ($row as $key => $value) {
            $row[$key] = str_replace('/ru/catalog/', '', $value);
          }
          $data[] = $row;
        }

        if ($data == '') {
          echo 'hello';

          die();
        }


        fclose($handle);

        $file_name = str_replace('.csv', '', $_FILES["fileToUpload"]["name"]);
        $fln = explode('.', $file_name);
        $fl_txt = $fln[0];
        $fl = explode('-', $file_name); // year and mount 

        $year = $fl[0];
        $y = intval($fl[0] - 2000);  //years
        $m = intval($fl[1]); //mounth
        $count_data = count($data);
        $db_count = $count_data - 10;


        $check = mysqli_query($conn, "SELECT id FROM stats WHERE y = $y AND m = $m");

        $nnn = 0;
        $num1 = 0;
        $num2 = 0;



        if ($check) {
          $check2 = mysqli_num_rows($check);
          $quantity = $check2;

          if ($quantity > 0 && $quantity == $db_count) {
            echo "The database already has data for $year-$m";
          } elseif ($quantity > $db_count) {
            echo "The database has more data then uploaded file ($quantity > $db_count)";
          } elseif ($quantity != 0 && $quantity < $db_count) {
            echo "The file has more datas then Database ($db_count > $quantity)";
          } elseif ($quantity == 0) {
            for ($i = 10; $i < $count_data; $i++) {
              $err = '';

              if (strpos($data[$i][0], '/')) {
                $pr = explode('/', $data[$i][0]);

                $pr1 = $pr[1];

                if (strpos($pr1, ',')) {

                  $inner_array = explode(',', $pr1);

                  $array3 = array_slice($inner_array, 0, 1);

                  $result_string = implode(',', $array3);

                  $pr_id = isset($result_string) ? $result_string : $pr1;

                }
                $rubric_name = $pr[0]; //string rubric name 
                $product_id = isset($pr_id) ? $pr_id : $pr[1]; // product id
                $parts = explode('/', $data[$i][0]);

                if (isset($parts[1])) {
                  $second_part = $parts[1];

                  $numbers = explode(',', $second_part);

                  if (count($numbers) >= 2) {
                    $first_number = $numbers[0];
                    $second_number = $numbers[1];
                  }

                  $n = isset($second_number) ? $second_number : $data[$i][1];
                }
                $count = isset($n) ? $n : $data[$i][1];
                $query = mysqli_query($conn, "SELECT id FROM rubrics WHERE link = '{$rubric_name}'");

                if (!$query) {
                  $err .= "<br> Something went wrong..!";
                }

                $rub_id = array();
                while ($row = mysqli_fetch_assoc($query)) { //link

                  $rub_id[] = $row['id'] . "<br>";
                  $rb = array_shift($rub_id);
                }

                $y = $fl[0] - 2000; //years
                $m = $fl[1]; //mounth
                $id_rub = $rb;
                $id_pro = $product_id ? $product_id : 0;
                $kol = $count;

                if ($err == '')

                  $stmt = $conn->prepare("INSERT INTO stats (y, m, id_rubric, id_product, kol) VALUES (?, ?, ?, ?, ?)");

                if ($stmt) {
                  $stmt->bind_param("iiiii", $y, $m, $id_rub, $id_pro, $kol);

                  if ($stmt->execute()) {

                    $message = 'success';
                    $nnn++;
                    $num1++;
                  } else {
                    $err .= '<br>wrong123';
                  }

                  $stmt->close();
                } else {
                  $err .= '<br>wrong123';
                }

              } else if (!strpos($data[$i][0], '/')) {
                // $pr = explode('/', $data[$i][0]);
                $pr = $data[$i][0];
                $rubric_name = $pr; //string rubric name 
                $product_id = 0; // product id
                $count = isset($n) ? $n : $data[$i][1];
                $query = mysqli_query($conn, "SELECT id FROM rubrics WHERE link = '{$rubric_name}'");
                if (!$query) {
                  $err .= "<br> Something went wrong..!";
                }
                $rub_id = array();
                while ($row = mysqli_fetch_assoc($query)) { //link

                  $rub_id[] = $row['id'] . "<br>";
                  $rb = array_shift($rub_id);
                }
                $id_rub = $rb;
                $id_pro = $product_id;
                $kol = $count;
                if ($err == '' && $id_pro == 0) {
                  $stmt = $conn->prepare("INSERT INTO stats (y, m, id_rubric, id_product, kol) VALUES (?, ?, ?, ?, ?)");

                  if ($stmt) {
                    $stmt->bind_param("iiiii", $y, $m, $id_rub, $id_pro, $kol);

                    if ($stmt->execute()) {
                      $message = 'success';
                      $nnn++;
                      $num2++;
                    } else {
                      $err .= '<br>wrong123';
                    }
                    $stmt->close();
                  } else {
                    echo "Error preparing statement: " . $conn->error;
                  }
                }
              } else {
                $err .= 'wrong';
              }
              echo $err;
            }
          } else {
            $err .= "Это файл ужи загружен!";
          }

        } else {
          echo "Could not select from db by year and month";
        }

        if ($message != '') {

          $txtFilePath = "$fl_txt.txt";

          $txtFilePath = mb_convert_encoding($txtFilePath, 'UTF-8');

          $handle = fopen($filePath, "r"); //from post

          $txtHandle = fopen("assets/txt/" . $txtFilePath, "w"); // txt file open or create

          while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
            foreach ($row as $key => $value) {
              $row[$key] = $value;
            }

            $line = implode(",", $row);

            fwrite($txtHandle, $line . PHP_EOL);
          }

          fclose($handle);
          fclose($txtHandle);
          // header('refresh:2;url=/test/index.php');
          echo "File muvaffaqiyatli txt formatda saqlandi <br>" . "Success!<br>" . "Total: " . "<strong>" . ($db_count) . "</strong>" . " , Inserted " . "<strong>" . $nnn . "</strong>" . "<br>" . "With product id: " . "<strong>" . $num1 . "</strong>" . " , <br> Only has rubrics: " . "<strong>" . $num2 . "</strong>" . "<br>";
        }
      } else {
        echo "Error opening the file.";
      }
    }
  }

  function processCSV($filePath)
  {
    $handle = fopen($filePath, "r");
    if ($handle !== FALSE) {
      while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {

        echo "<pre>";
        print_r($data);
        echo "</pre>";
      }
      fclose($handle);
    } else {
      echo "Error opening the file.";
    }
  }
}