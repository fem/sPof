<html>
  <head>
    <title>403 {t domain='spof'}Forbidden{/t}</title>
  </head>
  <body>
    <h1>{t domain='spof'}Forbidden{/t} ;-p</h1>
    <p>{t domain='spof' 1=$smarty.server.REQUEST_URI|escape}The requested URL %1 was not found on this server.{/t}</p>
    <hr />
    {if isset($smarty.server.SERVER_SIGNATURE)}
    <address>{$smarty.server.SERVER_SIGNATURE}</address>
    {/if}
  </body>
</html>
