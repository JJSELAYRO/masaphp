<?php
include 'conn.php';
if(isset($_GET['id'])){
	$id=$_GET['id'];
	$sql="DELETE FROM users WHERE id='$id'";
	$result=mysqli_query($conn,$sql);
	if($result){
		echo "
		<script>
		alert('user successfully deleted!!!');
		window.location.href = '../index.php';
		</script>";
		exit();
	}
}
?>