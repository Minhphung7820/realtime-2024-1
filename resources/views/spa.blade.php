<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>App</title>
  @vite('resources/css/app.css')
</head>

<body>
  <div id="app"></div>
  <script>
    window.baseURL = "{{ env('APP_URL') }}";
  </script>
  @vite('resources/js/app.js')
</body>

</html>