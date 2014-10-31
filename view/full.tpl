<html>
<head>
<meta name="viewport"
	content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
{foreach $METAS as $meta}
<meta name="{$meta@key}" content="{$meta}">
{/foreach}

<title>{$TITLE}</title>

{foreach $CSS_FILES as $link}
<link  name={$link@key} rel="stylesheet" type="text/css" href="{$link}"/>
{/foreach} 

<script type="text/javascript">
var RESOURCE_PATH = '{$RESOURCE_PATH}';
var CONTEXT_PATH = '{$CONTEXT_PATH}';
</script>
{foreach $SCRIPT_FILES as $src}
<script name={$src@key} src="{$src}" type="text/javascript"></script>
{/foreach}

</head>
<body>

{include file="$BODY_FILES" title="body"}

<div id="templates" style="height:0px;"></div>
<div id="page_json" data-value='{$page_json}'></div>
<div id="script_logs">


</div>
</body>
</html>
