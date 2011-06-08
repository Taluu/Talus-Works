<?php echo '<?'; ?>xml version="1.0" encoding="utf-8" ?>
<?php echo $tpl->vars['DOCTYPE']; ?>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
  <head>
    <title><?php echo $tpl->vars['TITLE']; ?> &bull; Talus' Works<?php if (IS_LOCAL == true) : ?> &bull; Local<?php endif; ?></title>

    <!-- Meta Tags //-->
    <meta name="keywords" content="developpement web, web, developpement, php, tpl, template, talus, talus works, works, talus tpl, moteur, compile, cache, work, scripts, documentation, doc, dynamique, rapide, rapidite, abstraction layer, support, forums, forum" />
    <meta name="description" content="<?php echo $tpl->vars['SITE_DESC']; ?>" />
    <meta http-equiv="pragma" content="no-cache" />
    <meta http-equiv="Content-Type" content="<?php echo $tpl->vars['CONTENT_TYPE']; ?>;charset=utf-8"/>
    <meta http-equiv="X-UA-Compatible" content="IE=8" />
    <meta name="identifier-URL" content="http://www.talus-works.net" />
    <!--  <meta name="verify-v1" content="xkWsXFNap6YljUxtN9pD569y3EUIgmxdxZYQl52UlpM=" />//-->
    <meta name="verify-v1" content="hFYf+EoUCI8Qzs9ujQlt6UZnSEnrDsCPQffgh1BZH+A=" />
    <meta name="robots" content="index,follow" />
    <meta name="copyrights" content="Talus &copy;2007 - <?php echo $tpl->vars['COPY_DATE']; ?>" />
    <meta name="powered" content="Talus' Works" />

    <!-- Styles //-->
    <link rel="stylesheet" href="./tpl/styles/style.css" type="text/css" media="all" />

    <?php if ($tpl->getBlock('style_css')) : $__tpl_aa38cc745831340c651a7a7cafa47df2d7f0b06f = &$tpl->getBlock('style_css'); foreach ($__tpl_aa38cc745831340c651a7a7cafa47df2d7f0b06f as &$__tplBlock['style_css']){ ?>
      <?php if ($__tplBlock['style_css']['FIRST']) : ?><!-- Regles d'import //--><style type="text/css"><?php endif; ?>
      @import "./tpl/styles/<?php echo $__tplBlock['style_css']['IMPORT']; ?>.css";
      <?php if ($__tplBlock['style_css']['LAST']) : ?></style><?php endif; ?>
    <?php } endif; ?>

    <!-- RSS //-->
    <link rel="alternate" href="http://feeds.feedburner.com/tw-last-topics" type="application/rss+xml" title="Derniers sujets postés sur Talus' Works" />
    <link rel="alternate" href="http://feeds.feedburner.com/tw-last-messages" type="application/rss+xml" title="Derniers messages postés sur Talus' Works" />
    <?php foreach ($tpl->vars['RSS'] as $__tpl_foreach_key['RSS'] => &$__tpl_foreach_value['RSS']) : ?>
      <link rel="alternate" href="<?php echo $__tpl_foreach_value['RSS']['href']; ?>" type="application/rss+xml" title="<?php echo $__tpl_foreach_value['RSS']['title']; ?>" />
    <?php endforeach; ?>

    <?php if ($tpl->getBlock('js')) : $__tpl_0c46f2a32fe4709ae1a476865373049267a3db7d = &$tpl->getBlock('js'); foreach ($__tpl_0c46f2a32fe4709ae1a476865373049267a3db7d as &$__tplBlock['js']){ ?>
      <?php if ($__tplBlock['js']['FIRST']) : ?><script type="text/javascript"><?php endif; ?>
      <?php echo $__tplBlock['js']['LINE']; ?>
      <?php if ($__tplBlock['js']['LAST']) : ?></script><?php endif; ?>
    <?php } endif; ?>

    <?php if ($tpl->getBlock('orphan_tags')) : $__tpl_e44765b948bcf4e7672b9160194cc34120b671f2 = &$tpl->getBlock('orphan_tags'); foreach ($__tpl_e44765b948bcf4e7672b9160194cc34120b671f2 as &$__tplBlock['orphan_tags']){ ?>
      <?php if ($__tplBlock['orphan_tags']['FIRST']) : ?><!-- Balises orphelines supplémentaires. !--><?php endif; ?>
      <<?php echo $__tplBlock['orphan_tags']['NAME'];  if (isset($__tplBlock['orphan_tags']['attr'])) : $__tpl_6878816af4bc27b084d9f5ed94e89a88f1893af2 = &$__tplBlock['orphan_tags']['attr']; foreach ($__tpl_6878816af4bc27b084d9f5ed94e89a88f1893af2 as &$__tplBlock['attr']){  echo $__tplBlock['attr']['NAME']; ?>="<?php echo $__tplBlock['attr']['VALUE']; ?>" <?php } endif; ?>/>
    <?php } endif; ?>
  </head>
  <body>
    <div id="box"></div>
    <div id="ajax_waiter">Chargement...</div>
    <div id="container"> 
    <!--[if lt IE 7]>
      <div style='border: 1px solid #F7941D; background: #FEEFDA; text-align: center; clear: both; height: 75px; position: relative;'>
        <div style='position: absolute; right: 3px; top: 3px; font-family: courier new; font-weight: bold;'><a href='#' onclick='javascript:this.parentNode.parentNode.style.display="none"; return false;'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-cornerx.jpg' style='border: none;' alt='Close this notice'/></a></div>  
        <div style='width: 640px; margin: 0 auto; text-align: left; padding: 0; overflow: hidden; color: black;'>
        <div style='width: 75px; float: left;'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-warning.jpg' alt='Warning!'/></div>
          <div style='width: 275px; float: left; font-family: Arial, sans-serif;'>
            <div style='font-size: 14px; font-weight: bold; margin-top: 12px;'>Vous utilisez un navigateur dépassé depuis près de 8 ans!</div>
            <div style='font-size: 12px; margin-top: 6px; line-height: 12px;'>Pour une meilleure expérience web, prenez le temps de mettre votre navigateur à jour.</div>
          </div>
          
          <div style='width: 75px; float: left;'><a href='http://fr.www.mozilla.com/fr/' target='_blank'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-firefox.jpg' style='border: none;' alt='Get Firefox 3.5'/></a></div>
          <div style='width: 75px; float: left;'><a href='http://www.microsoft.com/downloads/details.aspx?FamilyID=341c2ad5-8c3d-4347-8c03-08cdecd8852b&DisplayLang=fr' target='_blank'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-ie8.jpg' style='border: none;' alt='Get Internet Explorer 8'/></a></div>
          <div style='width: 73px; float: left;'><a href='http://www.apple.com/fr/safari/download/' target='_blank'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-safari.jpg' style='border: none;' alt='Get Safari 4'/></a></div>
          <div style='float: left;'><a href='http://www.google.com/chrome?hl=fr' target='_blank'><img src='http://www.ie6nomore.com/files/theme/ie6nomore-chrome.jpg' style='border: none;' alt='Get Google Chrome'/></a></div>
        </div>
      </div>
    <![endif]-->
    
    <div id="header">
    <div class="userinfo">
        <div class="left">
          Bonjour
          <?php if ($tpl->vars['IS_LOGGED']) : ?>
            <?php echo $tpl->vars['USERNAME']; ?>
            <?php if ($tpl->vars['NB_MESSAGES'] > 0) : ?>Vous avez <?php echo $tpl->vars['NB_MESSAGES']; ?> nouveaux messages privés<?php endif; ?>
          <?php else : ?>
            Visiteur, vous n'êtes pas connecté.
          <?php endif; ?>
        </div> <!-- end container > header > userinfo > left //-->

        <div class="right">
        <?php if ($tpl->vars['IS_LOGGED']) : ?>
          <?php if ($tpl->vars['IS_MODO']) : ?>
            <?php if ($tpl->vars['IS_ADMIN']) : ?>Administration - <?php endif; ?>
            <a href="moderation.html">Modération</a> |  
          <?php endif; ?> 
          
          Mon Profil - Messages... Non Lus | Postés | Privés - <a href="logout-<?php echo $tpl->vars['U_ID']; ?>.html">Déconnexion</a>
        <?php else : ?>
          <a href="login.html">Connexion</a> - <a href="register.html">Inscription</a>
        <?php endif; ?> 
      </div> <!-- end container > header > userinfo > right //-->
    </div> <!-- end container > header > userinfo //-->

    <div class="logo"><img src="<?php echo $tpl->vars['LOGO_ALEATOIRE']; ?>" alt="Talus' Works" class="img_logo" /></div>
    
    <div class="menu">
      <span class="gras grand">&#171;</span> 
      <a href="http://www.talus-works.net">Home</a> - 
      <a href="http://blog.talus-works.net">Blog</a> - 
      <a href="/forum-13-p1-releases.html">Téléchargements</a> -
      <a href="/explorer.html" title="Explorateur de Sources">Explorateur</a> -
      A propos
      <span class="gras grand">&#187;</span>
    </div><!-- end container > header > menu //--> 
    
    <div id="google_container" class="google">
      <!-- http://www.cromwell-intl.com/technical/google-adsense-and-xhtml.html //-->
      <!--[if IE]><?php $tpl->includeTpl('./google/adsense_main.html', false, 0); ?><![endif]-->
      <!--[if !IE]><!--><object data="./tpl/files/google/adsense_main.html" type="text/html" class="google" /><!--><![endif]-->
    </div><!-- end container > header > google_main //-->
  </div> <!-- end container > header //-->

  <div id="middle">
    <?php $tpl->includeTpl('navigation.html', false, 0); ?>