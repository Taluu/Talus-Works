			</div>
			<div id="footer">
				<div class="clear">&nbsp;</div>
				<div id="exec">
					Page chargée en <?php echo $tpl->vars['EXEC_TIME']; ?>s (<?php echo $tpl->vars['EXEC_SQL']; ?>s) - <?php echo $tpl->vars['NBR_SQL']; ?> requête<?php if ($tpl->vars['NBR_SQL'] != 1) : ?>s<?php endif; ?> - <span title="Basé sur les 5 dernières minutes"><?php echo $tpl->vars['NBR_CONNECTES']; ?> visiteur<?php if ($tpl->vars['NBR_CONNECTES'] != 1) : ?>s<?php endif; ?></span><br />
					<a href="#header" title="Haut de Page">Remonter</a> - <a href="contact.html">Contacter le Webmaster</a> - <a href="cgu.html" title="Condition Générales d'Utilisation">CGU</a> - <a href="http://validator.w3.org/check?uri=http://www.talus-works.net">Valide xHTML Strict</a> - <a href="http://jigsaw.w3.org/css-validator/validator?uri=http://www.talus-works.net">Valide CSS</a>
				</div>
				<div id="copy">
					Copyrights &copy;2007 - <?php echo $tpl->vars['COPY_DATE']; ?> <a href="http://www.talus-works.net">Talus' Works</a> - <a href="http://www.huuu.fr/">Personnages "Huuu" par Alexandre</a><br />
					Toute reproduction totale ou partielle est interdite sans l'accord des auteurs.
				</div>
			</div>
		</div>
		
        <!-- Scripts JS //-->
        <script type="text/javascript" src="http://www.google-analytics.com/ga.js"></script>
		<script type="text/javascript">
			var pageTracker = _gat._getTracker("UA-338155-6");
			pageTracker._initData();
			pageTracker._trackPageview();
		</script>
        <script type="text/javascript" src="./includes/js/common.js"></script>
        <?php foreach ($tpl->vars['JS'] as $__tpl_foreach_key['JS'] => &$__tpl_foreach_value['JS']) : ?>
            <script type="text/javascript" src="./includes/js/<?php echo $__tpl_foreach_value['JS']; ?>.js"></script>
        <?php endforeach; ?>
        <!--[if lt IE 7]><script type="text/javascript" src="sleight.js"></script><![endif]-->
	</body>
</html>