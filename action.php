<?php
	session_start();
	require 'config.php';

	// Add products into the cart table
	if (isset($_POST['pid'])) {
	  $pid = $_POST['pid'];
	  $pname = $_POST['pname'];
	  $pprice = $_POST['pprice'];
	  $pimage = $_POST['pimage'];
	  $pcode = $_POST['pcode'];
	  $pqty = $_POST['pqty'];
	  $total_price = $pprice * $pqty;

	  $stmt = $conn->prepare('SELECT product_code FROM cart WHERE product_code=?');
	  $stmt->bind_param('s',$pcode);
	  $stmt->execute();
	  $res = $stmt->get_result();
	  $r = $res->fetch_assoc();
	  $code = $r['product_code'] ?? '';

	  if (!$code) {
	    $query = $conn->prepare('INSERT INTO cart (product_name,product_price,product_image,qty,total_price,product_code) VALUES (?,?,?,?,?,?)');
	    $query->bind_param('ssssss',$pname,$pprice,$pimage,$pqty,$total_price,$pcode);
	    $query->execute();

	    echo '<div class="alert alert-success alert-dismissible mt-2">
						  <button type="button" class="close" data-dismiss="alert">&times;</button>
						  <strong>Item added to your cart!</strong>
						</div>';
	  } else {
	    echo '<div class="alert alert-danger alert-dismissible mt-2">
						  <button type="button" class="close" data-dismiss="alert">&times;</button>
						  <strong>Item already added to your cart!</strong>
						</div>';
	  }
	}

	// Get no.of items available in the cart table
	if (isset($_GET['cartItem']) && isset($_GET['cartItem']) == 'cart_item') {
	  $stmt = $conn->prepare('SELECT * FROM cart');
	  $stmt->execute();
	  $stmt->store_result();
	  $rows = $stmt->num_rows;

	  echo $rows;
	}

	// Remove single items from cart
	if (isset($_GET['remove'])) {
	  $id = $_GET['remove'];

	  $stmt = $conn->prepare('DELETE FROM cart WHERE id=?');
	  $stmt->bind_param('i',$id);
	  $stmt->execute();

	  $_SESSION['showAlert'] = 'block';
	  $_SESSION['message'] = 'Item removed from the cart!';
	  header('location:cart.php');
	}

	// Remove all items at once from cart
	if (isset($_GET['clear'])) {
	  $stmt = $conn->prepare('DELETE FROM cart');
	  $stmt->execute();
	  $_SESSION['showAlert'] = 'block';
	  $_SESSION['message'] = 'All Item removed from the cart!';
	  header('location:cart.php');
	}

	// Set total price of the product in the cart table
	if (isset($_POST['qty'])) {
	  $qty = $_POST['qty'];
	  $pid = $_POST['pid'];
	  $pprice = $_POST['pprice'];

	  $tprice = $qty * $pprice;

	  $stmt = $conn->prepare('UPDATE cart SET qty=?, total_price=? WHERE id=?');
	  $stmt->bind_param('isi',$qty,$tprice,$pid);
	  $stmt->execute();
	}

	// Checkout and save customer info in the orders table
	if (isset($_POST['action']) && isset($_POST['action']) == 'order') {
	  $name = $_POST['name'];
	  $email = $_POST['email'];
	  $phone = $_POST['phone'];
	  $products = $_POST['products'];
	  $grand_total = $_POST['grand_total'];
	  $address = $_POST['address'];
	  $pmode = $_POST['pmode'];

	  $data = '';

	  $stmt = $conn->prepare('INSERT INTO orders (name,email,phone,address,pmode,products,amount_paid)VALUES(?,?,?,?,?,?,?)');
	  $stmt->bind_param('sssssss',$name,$email,$phone,$address,$pmode,$products,$grand_total);
	  $stmt->execute();
	  $stmt2 = $conn->prepare('DELETE FROM cart');
	  $stmt2->execute();
	  /**********************************************************************************/
	  /*********************************************************************************/
	// Récupérer la quantité de chaque produit acheté
$quantities = [];
$productList = explode(',', $products);

foreach ($productList as $product) {
    list($product_name, $qty) = explode('(', str_replace(')', '', $product));
    $quantities[$product_name] = (int) $qty;
}

// Maintenant, $quantities contient les quantités de chaque produit acheté.
// Vous pouvez accéder aux quantités individuelles en utilisant le nom du produit comme clé dans le tableau $quantities.

// Exemple d'utilisation :
foreach ($quantities as $product_name => $quantity) {
    echo 'Produit : ' . $product_name . ', Quantité : ' . $quantity . '<br>';
}



    /******************************************************************************/
    // Récupérer les noms des produits achetés dans un tableau
$productsPurchased = [];

foreach ($quantities as $product_name => $quantity) {
    $productsPurchased[] = $product_name;
}

// Maintenant, $productsPurchased contient les noms des produits achetés.
// Vous pouvez les afficher ou effectuer d'autres opérations avec ce tableau.
// Par exemple, vous pouvez l'afficher en utilisant la fonction implode() pour obtenir une chaîne séparée par des virgules.

echo 'Produits achetés : ' . implode(', ', $productsPurchased);

    /****************************************************************************/
// Avant la boucle foreach
$productStocks = []; // Initialisez un tableau vide pour stocker les stocks des produits

foreach ($quantities as $product_name => $quantity) {
    // Effectuez une requête SQL pour obtenir le stock du produit $product_name de votre base de données
    $stmt = $conn->prepare('SELECT stock FROM product WHERE product_name = ?');
    $stmt->bind_param('s', $product_name);
    $stmt->execute();
    $stmt->bind_result($stock);
    $stmt->fetch();
    $stmt->close();

    // Stockez le stock du produit dans le tableau associatif
    $productStocks[$product_name] = $stock;

    // Maintenant, $productStocks contient le stock de chaque produit
    echo 'Produit : ' . $product_name . ', Quantité : ' . $quantity . ', Stock disponible : ' . $stock . '<br>';
}

// Vous pouvez accéder aux stocks individuels en utilisant le nom du produit comme clé dans le tableau $productStocks
// Par exemple, $productStocks['NomDuProduit'] renverra le stock de ce produit.


    /*****************************************************************************/
 

// Fonction callback pour soustraire les éléments des deux tableaux
$newstock = array_map(function($a, $b) {
    return $a - $b;
},  $productStocks, $quantities);

// $result contient le résultat de la soustraction
print_r($newstock);



    /****************************************************************************/
 

  // Mettez à jour le stock dans la table product
foreach ($newstock as $product_name => $stock) {
    $stmt = $conn->prepare('UPDATE product SET stock = ? WHERE product_name = ?');
    $stmt->bind_param('is', $stock, $product_name);
    $stmt->execute();
}
    /****************************************************************************/
    
   
	  $data .= '<div class="text-center">
								<h1 class="display-4 mt-2 text-danger">Thank You!</h1>
								<h2 class="text-success">Your Order Placed Successfully!</h2>
								<h4 class="bg-danger text-light rounded p-2">Items Purchased : ' . $products . '</h4>
								<h4>Your Name : ' . $name . '</h4>
								<h4>Your E-mail : ' . $email . '</h4>
								<h4>Your Phone : ' . $phone . '</h4>
								<h4>Total Amount Paid : ' . number_format($grand_total,2) . '</h4>
								<h4>Payment Mode : ' . $pmode . '</h4>
						  </div>';
	  echo $data;
	}
?>

