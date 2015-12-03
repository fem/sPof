<html>
<head>
  <title>500 {t domain='spof'}Internal Server Error{/t}</title>
</head>
<body>
<h1>{t domain='spof'}Internal Server Error{/t} ;-(</h1>
  <p>{t domain='spof'}The requested URL {$smarty.server.REQUEST_URI|escape} was not found on this server.{/t}</p>
<hr />
{if isset($smarty.server.SERVER_SIGNATURE)}
<address>{$smarty.server.SERVER_SIGNATURE}</address>
{/if}
</body>
</html>
