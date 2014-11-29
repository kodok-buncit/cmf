<!doctype html>
<html>
<head>
	<title>Welcome</title>
	<link rel="stylesheet" href="css/bootstrap.min.css" media="screen">
	<link rel="stylesheet" href="css/bootstrap-theme.min.css" media="screen">

	<script type="text/javascript" src="js/jquery.min.js"></script>
	<script type="text/javascript" src="js/bootstrap.min.js"></script>
</head>
<body>

<div class="container">
	@section('sidebar')
	    This is the master sidebar.
	@show

    @yield('content')
</div>

</body>
</html>