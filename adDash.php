<?php
include("../Database/Database.php");
include("../Headers/DashboardHeader.html");

$foodID = null;
$foodname = null;
$cuisinetype = null;
$foodtype = null;
$description = null;
$price = null;
$image = null;
$imgContent = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {

  if (isset($_POST["updateItem"])) {
    $foodID = $_POST["selectedID"];
    $foodname = $_POST["selectedName"];
    $cuisinetype = $_POST["selectedCuisineType"];
    $foodtype = $_POST["selectedFoodType"];
    $description = $_POST["selectedDescription"];
    $price = $_POST["selectedPrice"];
    $image = $_POST["selectedImage"];
    $_POST["newFoodImage"] = $image;
  }

  if(isset($_POST["addNewMenu"]) && empty($_POST["newID"])){
    $foodname = $_POST["newName"];
    $cuisinetype = $_POST["newCuisineType"];
    $foodtype = $_POST["newFoodType"];
    $description = $_POST["newDescription"];
    $price = $_POST["newPrice"];

    if(isset($_FILES['newFoodImage']) && $_FILES['newFoodImage']['error'] == 0){
      $fileTmpPath = $_FILES['newFoodImage']['tmp_name'];
      $imgContent = addslashes(file_get_contents($fileTmpPath));
    }

    $sql = "INSERT INTO menu (Name, CuisineType, FoodType, Description, Price, Image) VALUES ('{$foodname}','{$cuisinetype}','{$foodtype}','{$description}',{$price},'{}{$imgContent}')";

    if (mysqli_query($conn, $sql)) {
      echo "<script> console.log('Record updated successfully');</script>";
    } else {
      echo "<script> console.log('Error updating record: " . mysqli_error($conn)."');</script>";
    }
  }

  if (isset($_POST["updateMenu"]) && isset($_POST["newID"])) {

    if(isset($_FILES['newFoodImage']) && $_FILES['newFoodImage']['error'] == 0){
      $fileTmpPath = $_FILES['newFoodImage']['tmp_name'];
      $imgContent = addslashes(file_get_contents($fileTmpPath));
    } else {
      // Handle cases where the image file is not updated
      $base64String = trim($_POST["newImage"]);
      $imgContent = addslashes(base64_decode($base64String));
    }

    $sql = "UPDATE menu SET 
    Name='".$_POST["newName"]."', 
    CuisineType='".$_POST["newCuisineType"]."',
    FoodType='".$_POST["newFoodType"]."',
    Description='".$_POST["newDescription"]."',
    Price='".$_POST["newPrice"]."', 
    Image='".$imgContent."'
    WHERE MID='".$_POST["newID"]."'";

    if (mysqli_query($conn, $sql)) {
      echo "<script> console.log('Record updated successfully');</script>";
    } else {
      echo "<script> console.log('Error updating record: " . mysqli_error($conn)."');</script>";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<script>
  function submitForm() {
    const foodType = document.getElementById('foodType').value;
    const description = document.getElementById('foodDescription').value;
    alert(foodType + " " + description);
    document.getElementById('newFoodType').value = foodType;
    document.getElementById('newDescription').value = description;
  }
</script>

<body style="margin-top: 100px;">
  <div class="container-fluid mt-5">
    <div class="row">
      <!-- Left Grid -->
      <div class="col-md-3">
        <div class="card">
          <div class="card-header">
            <h4>Add/Edit Food Item</h4>
          </div>
          <div class="card-body">
            <form action="" method="post" enctype="multipart/form-data" id="updateForm">
              <input type="hidden" name="newID" value="<?php echo $foodID ?>">
              <input type="hidden" name="newImage" value="<?php echo $image ?>">
              <input type="hidden" name="newFoodType" id="newFoodType">
              <input type="hidden" name="newDescription" id="newDescription">
              <div class="form-group">
                <img src='<?php if (isset($image)) echo "data:image/jpeg;base64," . $image;
                          else echo "../logo.jpg"; ?>' alt="Food Image" class="img-fluid mb-2" id="foodImagePreview" width="100px" height="auto">
                <input type="file" class="form-control-file" name="newFoodImage" id="foodImage">
              </div>
              <div class="form-group">
                <label for="foodName">Food Name</label>
                <input type="text" class="form-control" name="newName" id="foodName" placeholder="Enter food name" value="<?php echo $foodname ?>">
              </div>
              <div class="form-group">
                <label for="cuisineType">Cuisine Type</label>
                <input type="text" class="form-control" name="newCuisineType" id="cuisineType" placeholder="Enter cuisine type" value="<?php echo $cuisinetype ?>">
              </div>
              <div class="form-group">
                <label for="foodType">Food Type</label>
                <select class="form-control" id="foodType">
                  <option <?php if ($foodtype == "Main Course") echo 'selected'; ?>>Main Course</option>
                  <option <?php if ($foodtype == "Side Dish") echo 'selected'; ?>>Side Dish</option>
                  <option <?php if ($foodtype == "Appetizer") echo 'selected'; ?>>Appetizer</option>
                  <option <?php if ($foodtype == "Soup") echo 'selected'; ?>>Soup</option>
                  <option <?php if ($foodtype == "Salad") echo 'selected'; ?>>Salad</option>
                  <option <?php if ($foodtype == "Drink") echo 'selected'; ?>>Drink</option>
                  <option <?php if ($foodtype == "Dessert") echo 'selected'; ?>>Dessert</option>
                </select>
              </div>
              <div class="form-group">
                <label for="foodDescription">Description</label>
                <textarea class="form-control" id="foodDescription" rows="3" placeholder="Enter description"><?php echo $description ?></textarea>
              </div>
              <div class="form-group">
                <label for="foodPrice">Price</label>
                <input type="text" class="form-control" name="newPrice" id="price" placeholder="Enter price" value="<?php echo $price ?>">
              </div>
              <br>
              <?php
              if (isset($foodname))
                echo "<input type='submit' class='btn btn-primary' name='updateMenu' value='Update' onclick='submitForm();'>";
              else
                echo "<input type='submit' class='btn btn-primary' name='addNewMenu' value='Add' onclick='submitForm();'>";
              ?>
            </form>
          </div>
        </div>
      </div>

      <!-- Right Grid -->
      <div class="col-md-9">
        <div class="card">
          <div class="card-header">
            <h4>Food Items</h4>
          </div>
          <div class="card-body">
            <table class="table table-striped">
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Food Name</th>
                  <th>Cuisine Type</th>
                  <th>Food Type</th>
                  <th>Description</th>
                  <th>Price</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php
                $sql = "SELECT * FROM menu";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td> <img src='data:image/jpeg;base64," . base64_encode($row['Image']) . "' alt='Avatar' class='image' width='50px' height='50px'> </td>";
                    echo "<td>" . $row['Name'] . "</td>";
                    echo "<td>" . $row['CuisineType'] . "</td>";
                    echo "<td>" . $row['FoodType'] . "</td>";
                    echo "<td>" . $row['Description'] . "</td>";
                    echo "<td>" . $row['Price'] . "</td>";

                    echo "<form id='updateForm' method='POST' action='' style='display:none;' enctype='multipart/form-data'>";
                    echo "<input type='hidden' name='selectedID' value='" . $row['MID'] . "'>";
                    echo "<input type='hidden' name='selectedImage' value='" . base64_encode($row['Image']) . "'>";
                    echo "<input type='hidden' name='selectedName' value='" . $row['Name'] . "'>";
                    echo "<input type='hidden' name='selectedCuisineType' value='" . $row['CuisineType'] . "'>";
                    echo "<input type='hidden' name='selectedFoodType' value='" . $row['FoodType'] . "'>";
                    echo "<input type='hidden' name='selectedDescription' value='" . $row['Description'] . "'>";
                    echo "<input type='hidden' name='selectedPrice' value='" . $row['Price'] . "'>";
                    echo "<td> <input type='submit' class='btn btn-success' name='updateItem' id='updateItem' value='&#128295;'> </td>";
                    echo "</form>";
                    echo "</tr>";
                  }
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
  <script>
    document.getElementById('foodImage').addEventListener('change', function(event) {
      const reader = new FileReader();
      reader.onload = function() {
        const output = document.getElementById('foodImagePreview');
        output.src = reader.result;
      };
      reader.readAsDataURL(event.target.files[0]);
    });
  </script>
</body>

</html>