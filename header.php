<!-- header.php -->
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>GWAST</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet"/>
  
  <style>
    body {
      background-color: #f8f6f0;
      /* background-color: #ffffff; */
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .navbar {
      background: linear-gradient(135deg, #B99C6B, #D4B896);
      padding: 15px 0;
      border-bottom: 2px solid #a58a5e;
    }

    .navbar-brand {
      font-weight: bold;
      font-size: 1.8rem;
      color: #fff;
      text-shadow: 1px 1px 1px #6b5232;
    }

    .navbar-text {
      color: #fff7ea;
      margin-left: 15px;
      font-weight: 500;
    }

    .nav-icons a {
      color: #fff;
      margin-left: 15px;
      font-size: 1.2rem;
    }

    .nav-icons a:hover {
      color: #f0e5ce;
    }
  </style>
</head>
<body>
  <nav class="navbar navbar-expand-md shadow-sm">
    <div class="container">
      <a class="navbar-brand" href="../../index.php">GWAST</a>
      <span class="navbar-text d-none d-md-inline">Tool for analysis of GWAS</span>
      <div class="ms-auto nav-icons">
        <a href="index.php"><i class="fas fa-home"></i></a>
        <a href="help.php"><i class="fas fa-question-circle"></i></a>
        <a href="about.php"><i class="fas fa-info-circle"></i></a>
      </div>
    </div>
  </nav>
