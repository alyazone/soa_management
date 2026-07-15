<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Website Status Checker</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5">
  <h2 class="mb-4">Website Status Checker</h2>

  <!-- URL Input Form -->
  <form method="POST" class="mb-3">
    <div class="input-group">
      <input type="text" name="url" class="form-control" placeholder="Enter website URL (e.g., https://example.com)" required>
      <button type="submit" class="btn btn-primary">Check Status</button>
    </div>
  </form>

  <?php
  // Function to check website status
  function checkWebsiteStatus($url) {
      $ch = curl_init($url);

      curl_setopt_array($ch, [
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_NOBODY => true,                // Only get headers
          CURLOPT_FOLLOWLOCATION => true,        // Follow redirects
          CURLOPT_TIMEOUT => 10,                 // Set timeout (10s)
          CURLOPT_USERAGENT => 'Mozilla/5.0 (Website Status Checker)', // Realistic user-agent
          CURLOPT_SSL_VERIFYPEER => false,       // Skip SSL verification for simplicity (can be true in prod)
      ]);

      curl_exec($ch);
      $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($ch);
      curl_close($ch);

      if ($curlErr) return ['status' => 'DOWN', 'code' => 0];
      if ($httpCode >= 200 && $httpCode < 400) return ['status' => 'UP', 'code' => $httpCode];
      return ['status' => 'DOWN', 'code' => $httpCode];
  }

  // Handle POST request
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $url = trim($_POST['url']);

      // Add http:// if missing
      if (!preg_match('#^https?://#i', $url)) {
          $url = 'http://' . $url;
      }

      $url = filter_var($url, FILTER_VALIDATE_URL);

      if (!$url) {
          echo '<div class="alert alert-danger">❌ Invalid URL format.</div>';
      } else {
          $result = checkWebsiteStatus($url);
          $status = $result['status'];
          $code = $result['code'];

          if ($status === 'UP') {
              echo "<div class='alert alert-success'>🟢 Website is <strong>UP</strong> (HTTP $code).</div>";
          } elseif ($code === 0) {
              echo "<div class='alert alert-danger'>🔴 Website is <strong>DOWN</strong> (No response).</div>";
          } else {
              echo "<div class='alert alert-warning'>🟠 Website responded with HTTP <strong>$code</strong>. Considered DOWN.</div>";
          }
      }
  }
  ?>
</div>

</body>
</html>
