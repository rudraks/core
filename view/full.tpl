<html>
<head>
<meta name="viewport"
	content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no"/>
{foreach $METAS as $meta}
<meta name="{$meta@key}" content="{$meta}">
{/foreach}

<title>{$TITLE}</title>

{foreach $header->css as $link}
<link  name={$link@key} rel="stylesheet" type="text/css" href="{$link.link}"/>
{/foreach} 

<script type="text/javascript">
var RESOURCE_PATH = '{$RESOURCE_PATH}';
var CONTEXT_PATH = '{$smarty.const.CONTEXT_PATH}';
{foreach $header->const as $const}var {$const@key} = '{$const}';{/foreach}
var RX_MODE_DEBUG = !!('{$smarty.const.RX_MODE_DEBUG}');
var RX_JS_MERGE = !!('{$smarty.const.RX_JS_MERGE}');
</script>
</head>
<body>

{include file="$BODY_FILES" title="body"}

<div id="templates" style="height:0px;"></div>
<div id="page_json" data-value='{$page_json}'></div>
<div id="script_logs"></div>

</body>

{if $header->const.RX_JS_MERGE}
   	{foreach $header->scripts_bundle as $file}
		<script name="{$file@key}"src="{$file}" type="text/javascript"></script>
   	{/foreach}
{else}
   	{foreach $header->scripts as $src}
		<script name={$src@key} src="{$src.link}" type="text/javascript"></script>
	{/foreach}
{/if}

</html>
