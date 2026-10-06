<html>
<head>
  <title>{$fileBaseName} - CiviCRM</title>
</head>
<body style="margin: 0; overflow: hidden">

<iframe id="coolframe" name="coolframe" title="Collabora Online"
        allowfullscreen allow="clipboard-read *; clipboard-write *"
        style="width:100%; height:100%; position:absolute; border: none;"></iframe>

<div style="display: none">
  <form id="coolform" name="coolform" target="coolframe" action="{$wopiUrl}" method="post">
    <input name="access_token" value="{$accessToken}" type="hidden">
    <input name="access_token_ttl" value="{$accessTokenTtlMs}" type="hidden">
  </form>
</div>

<script>
  const frame = document.getElementById("coolframe");
  const form = document.getElementById("coolform");
  frame.focus();
  form.submit();
</script>
</body>
</html>
