<?php echo '<?'; ?>xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" 
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
	<head>
		<title><?php echo $tpl->vars['TITLE']; ?> - Talus' Works</title>
		
		<!-- Meta Tags //-->
		<meta name="keywords" content="tpl, template, talus, talus works, works, talus tpl, moteur, compile, php, cache, work, scripts, documentation, doc, dynamique, rapide, rapidite" />
		<meta name="description" content="Les travaux de Talus (Talus' TPL, Talus' Works, ...)" />
		<meta http-equiv="pragma" content="no-cache" />
		<meta http-equiv="Content-Type" content="text/html;charset=utf-8"/>
		<?php if ($tpl->vars['TIME'] & MESSAGE_REDIRECTION_ENABLED) : ?><meta http-equiv="refresh" content="<?php echo MESSAGE_REDIRECTION_TIME; ?>;url=<?php echo DOMAIN_REDIRECT;  echo $tpl->vars['URL']; ?>" /><?php endif; ?>
		<meta name="robots" content="noindex,nofollow" />
		
		<!-- Tags Links (stylesheet, RSS, Copyright) //-->
		<link rel="stylesheet" href="./tpl/styles/message.css" type="text/css" media="screen, print" />
	</head>
	<body class="message"> 
		<div id="message">
			<h1 class="<?php echo $tpl->vars['CLASS_CSS']; ?>">Message #<?php echo $tpl->vars['ID_MESSAGE']; ?> :</h1>
			<span class="<?php echo $tpl->vars['CLASS_CSS']; ?>"><?php echo $tpl->vars['MESSAGE']; ?></span>
				<?php if (!$tpl->vars['BAN']) : ?>
					<p class="redirection">
						<?php if ($tpl->vars['TIME'] & MESSAGE_REDIRECTION_ENABLED) : ?>
							Vous allez être redirigé dans <?php echo MESSAGE_REDIRECTION_TIME; ?> secondes.<br />
							<span class="tpetit">Cliquez <a href="<?php echo DOMAIN_REDIRECT;  echo $tpl->vars['URL']; ?>">ici</a> si vous ne souhaitez pas attendre.</span>
						<?php else : ?>
							<a href="<?php echo DOMAIN_REDIRECT;  echo $tpl->vars['URL']; ?>">Cliquez ici pour continuer.</a>
						<?php endif; ?>
					</p>
				<?php endif; ?>
		</div>
	</body>
</html>