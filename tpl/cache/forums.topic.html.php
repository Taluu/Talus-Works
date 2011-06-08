<?php $tpl->includeTpl('head.html', false, 0); ?>
<div id="infos_topic">
    <h1><?php echo $tpl->vars['TOPIC_TITLE']; ?></h1>
    <?php if ($tpl->vars['TOPIC_DESCRIPTION']) : ?><div class="forum_description"><?php echo $tpl->vars['TOPIC_DESCRIPTION']; ?></div><?php endif; ?>
</div>

<?php if ($tpl->vars['TOPIC_SOLVED']) : ?>
    <div class="topic_solved">
        <img src="./images/icones/topic_solved.png" alt="Résolu" />
        Ce sujet est considéré comme "Résolu".
    </div>
<?php endif; ?>

<?php $tpl->includeTpl('forums/topics_info.html', false, 0); ?>

<table class="list_messages">
    <tbody>
        <?php if ($tpl->getBlock('posts')) : $__tpl_3fe6a4d50c2da883db78f2210fd812853dbac7bc = &$tpl->getBlock('posts'); foreach ($__tpl_3fe6a4d50c2da883db78f2210fd812853dbac7bc as &$__tplBlock['posts']){ ?>
            <tr class="post_date" id="p<?php echo $__tplBlock['posts']['ID']; ?>">
                <td colspan="2">
                    <span class="actions">
                        <?php if ($tpl->vars['IS_LOGGED']) : ?>
                            <img src="./images/icones/abuse.gif" alt="Abus" title="Reporter un abus" />
                            <?php if ($tpl->vars['IS_FORUM_MODO'] || $__tplBlock['posts']['IS_AUTHOR']) : ?>
                                <a href="edit-topic-<?php echo $__tplBlock['posts']['ID']; ?>.html">
                                    <img src="./images/icones/edit.gif" alt="Editer" title="Editer ce post" />
                                </a>
                            <?php endif; ?>
                            <?php if ($tpl->vars['IS_FORUM_MODO'] && !$__tplBlock['posts']['FIRST']) : ?>
                                <a href="moderation-delete-<?php echo $__tplBlock['posts']['ID']; ?>.html" onclick="return confirm('Êtes vous sûr de supprimer ce message ?');">
                                    <img src="./images/icones/delete.gif" alt="Supprimer" title="Supprimer ce post" />
                                </a>
                            <?php endif; ?>
                            <a href="reply-topic-<?php echo $tpl->vars['TOPIC_ID']; ?>-<?php echo $__tplBlock['posts']['ID']; ?>.html" onclick="quote(<?php echo $__tplBlock['posts']['ID']; ?>, 'reply'); return false;">
                                <img src="./images/icones/quote_off.gif" alt="Citer" title="Citer ce post" id="quote_<?php echo $__tplBlock['posts']['ID']; ?>" />
                            </a>
                        <?php endif; ?>
                        <a href="#header"><img src="./images/icones/up.gif" alt="Haut de Page" title="Haut de Page" /></a>
                    </span>
                    #<?php echo $__tplBlock['posts']['ID_POST']; ?> - <a href="#p<?php echo $__tplBlock['posts']['ID']; ?>"><?php echo $__tplBlock['posts']['DATE']; ?></a>
                </td>
            </tr>
            <tr class="messages">
                <td class="userinfo">
                    <ul class="userinfo">
                        <li class="user_name" title="<?php echo $__tplBlock['posts']['U_STATUS']; ?>"><img src="./images/icones/status_<?php echo $__tplBlock['posts']['U_LOGGED_STATUS']; ?>line.png" alt="<?php echo $__tplBlock['posts']['U_STATUS']; ?>" class="logged_status" /> <?php echo $__tplBlock['posts']['U_NAME']; ?></li>
                        <?php if ($__tplBlock['posts']['U_QUOTE']) : ?><li class="user_quote"><?php echo $__tplBlock['posts']['U_QUOTE']; ?></li><?php endif; ?>
                        <li class="user_avatar"><img src="http://www.gravatar.com/avatar/<?php echo $__tplBlock['posts']['U_AVATAR']; ?>" alt="Avatar" /></li>

                        <?php if ($__tplBlock['posts']['U_LVL'] <= GRP_MODO) : ?>
                            <li class="user_level"><span class="<?php echo $__tplBlock['posts']['U_CLASS']; ?>"><?php echo $__tplBlock['posts']['U_GRP']; ?></span></li>
                        <?php endif; ?>

                        <?php if ($tpl->vars['IS_FORUM_MODO']) : ?>
                            <li class="user_nbr_messages"><span class="gras">Messages :</span> <?php echo $__tplBlock['posts']['U_NBR_MESSAGES']; ?></li>
                            <li class="user_ip"><span class="gras">IP :</span> <?php echo $__tplBlock['posts']['U_IP']; ?></li>
                        <?php endif; ?>
                    </ul>
                </td>

                <td class="content" id="content_tr_p<?php echo $__tplBlock['posts']['ID']; ?>">
                    <div id="msg_edit_p<?php echo $__tplBlock['posts']['ID']; ?>">
                        <div class="message" id="content_<?php echo $__tplBlock['posts']['ID']; ?>"<?php if ($tpl->vars['IS_FORUM_MODO'] || $__tplBlock['posts']['IS_AUTHOR']) : ?> onmouseover="eip_title(<?php echo $__tplBlock['posts']['ID']; ?>)" ondblclick="edit_in_place(<?php echo $__tplBlock['posts']['ID']; ?>);"<?php endif; ?>>
                            <?php if ($__tplBlock['posts']['IS_PREVIOUS'] && $__tplBlock['posts']['FIRST']) : ?><strong>Dernier message de la page précédente :</strong><br /><?php endif; ?>
                            <?php echo $__tplBlock['posts']['CONTENT']; ?>
                        </div>
                        <?php if ($__tplBlock['posts']['EDIT_TIMES'] > 0) : ?><div class="edited" id="edit_p<?php echo $__tplBlock['posts']['ID']; ?>">Dernière édition <?php echo $__tplBlock['posts']['EDIT_DATE']; ?>, par <?php echo $__tplBlock['posts']['EDIT_USER']; ?></div><?php endif; ?>
                    </div>
                    <?php if ($__tplBlock['posts']['U_SIGNATURE']) : ?>
                        <div class="signature"><?php echo $__tplBlock['posts']['U_SIGNATURE']; ?></div>
                    <?php endif; ?>
                </td>
            </tr>

            <?php if ($__tplBlock['posts']['FIRST']) : ?>
                <tr class="google">
                    <td colspan="2">
                        <!-- http://www.cromwell-intl.com/technical/google-adsense-and-xhtml.html //-->
                        <!--[if IE]><?php $tpl->includeTpl('./google/adsense_topic.html', false, 0); ?><![endif]-->
                        <!--[if !IE]><!--><object data="./tpl/files/google/adsense_topic.html" type="text/html" class="google" /><!--><![endif]-->
                    </td>
                </tr>
            <?php endif; ?>
        <?php } endif; ?>
    </tbody>
</table>

<?php $tpl->includeTpl('forums/topics_info.html', false, 0); ?>

<?php if ($tpl->vars['TOPIC_OPENED']) : ?>
    <form action="reply-topic-<?php echo $tpl->vars['TOPIC_ID']; ?>.html" method="post">
        <fieldset id="fast_reply">
            <input type="hidden" name="from_fast_reply" value="1" />

            <legend><label for="reply">Réponse Rapide</label></legend>
            <textarea name="reply" rows="5" cols="40" id="reply"></textarea><br />

            <input type="submit" name="send" value="Répondre" /> <input type="submit" name="send" value="Plus d'options..." />
        </fieldset>
    </form>
<?php endif; ?>

<!--//--><div class="aftertopic"><!--//-->
    <form action="" method="post">
        <fieldset class="jumpbox">
            <select class="jumpbox" name="jump_to" onchange="jumpbox(this);">
                <optgroup label="En général...">
                    <option <?php if ($tpl->vars['CURRENT_JPBX'] == 0) : ?>selected="selected" <?php endif; ?>value="index.html">Index</option>
                    <?php if ($tpl->vars['IS_LOGGED']) : ?>
                        <option value="logout.html">Déconnexion</option>
                        <option disabled="disabled">Mon Profil</option>
                        <option disabled="disabled">Mes MPs</option>
                        <?php if ($tpl->vars['IS_MODO']) : ?>
                            <option disabled="disabled">Panneau de Modération</option>
                            <?php if ($tpl->vars['IS_ADMIN']) : ?>
                                <option disabled="disabled">Panneau d'Administration</option>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else : ?>
                        <option value="register.html">Inscription</option>
                        <option value="login.html">Connexion</option>
                    <?php endif; ?>
                </optgroup>
                <?php if ($tpl->getBlock('jumpbox_cat')) : $__tpl_e6efe0b1cb6030f3652774870300559216b11c4e = &$tpl->getBlock('jumpbox_cat'); foreach ($__tpl_e6efe0b1cb6030f3652774870300559216b11c4e as &$__tplBlock['jumpbox_cat']){ ?>
                    <optgroup label="<?php echo $__tplBlock['jumpbox_cat']['NAME']; ?>">
                        <?php if (isset($__tplBlock['jumpbox_cat']['forums'])) : $__tpl_f538a325c325192c3e137c8d7b7caaaa3d298206 = &$__tplBlock['jumpbox_cat']['forums']; foreach ($__tpl_f538a325c325192c3e137c8d7b7caaaa3d298206 as &$__tplBlock['forums']){ ?>
                            <option <?php if ($tpl->vars['CURRENT_JPBX'] != 0 && $tpl->vars['CURRENT_JPBX'] == $__tplBlock['forums']['ID']) : ?>selected="selected" <?php endif; ?>value="forum-<?php echo $__tplBlock['forums']['ID']; ?>-p1.html">|<?php echo $__tplBlock['forums']['LEVEL']; ?> -- <?php echo $__tplBlock['forums']['NAME']; ?></option>
                        <?php } endif; ?>
                    </optgroup>
                <?php } endif; ?>
            </select>
            <input type="submit" value="Go !" />
        </fieldset>
    </form>

    <div class="actions_modo">
        <ul class="modo_options">
            <li>Avertir un modérateur</li>
            <?php if ($tpl->vars['IS_FORUM_MODO'] || $tpl->vars['IS_AUTHOR']) : ?>
                <li><a href="moderation-solve-<?php echo $tpl->vars['TOPIC_ID']; ?>.html"><?php if ($tpl->vars['TOPIC_SOLVED']) : ?>Enlever<?php else : ?>Ajouter<?php endif; ?> le marqueur "Résolu" pour ce sujet</a></li>
                <?php if ($tpl->vars['IS_FORUM_MODO']) : ?>
                    <li><a href="moderation-post-it-<?php echo $tpl->vars['TOPIC_ID']; ?>.html"><?php if ($tpl->vars['TOPIC_POSTIT']) : ?>Enlever<?php else : ?>Ajouter<?php endif; ?> le marqueur "Post-It" de ce sujet</a></li>
                    <li><a href="moderation-lock-<?php echo $tpl->vars['TOPIC_ID']; ?>.html"><?php if ($tpl->vars['TOPIC_CLOSED']) : ?>Enlever<?php else : ?>Ajouter<?php endif; ?> le marqueur "Fermé" de ce sujet</a></li>
                    <li><a href="moderation-move-<?php echo $tpl->vars['TOPIC_ID']; ?>.html">Déplacer le sujet</a></li>
                    <li>Supprimer ce sujet</li>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>
<!--//--></div><!--//-->

<div class="clearer"></div>
<?php $tpl->includeTpl('foot.html', false, 0); ?>