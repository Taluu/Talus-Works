<?php $tpl->includeTpl('head.html', false, 0); ?>
                <h1><?php echo $tpl->vars['FORUM_TITLE']; ?></h1>
                <?php if ($tpl->vars['FORUM_DESCRIPTION']) : ?><div class="forum_description"><?php echo $tpl->vars['FORUM_DESCRIPTION']; ?></div><?php endif; ?>

                <?php if ($tpl->vars['SUB_FORUMS']) : ?>
                    <table class="list_forums">
                        <thead>
                            <tr>
                                <th colspan="2" class="lbl_cat">Nom du Forum</th>
                                <th class="lbl_nbr">Sujets</th>
                                <th class="lbl_nbr">Messages</th>
                                <th class="lbl_last">Dernier Message</th>
                            </tr>
                        </thead>
                        
                        <tfoot>
                            <tr>
                                <th colspan="2" class="lbl_cat">Nom du forum</th>
                                <th class="lbl_nbr">Sujets</th>
                                <th class="lbl_nbr">Messages</th>
                                <th class="lbl_last">Dernier Message</th>
                            </tr>
                        </tfoot>
                        
                        <tbody>								
                            <?php if ($tpl->getBlock('forums')) : $__tpl_ea7bc3b89f9e43e207bec553626512d48090fb3e = &$tpl->getBlock('forums'); foreach ($__tpl_ea7bc3b89f9e43e207bec553626512d48090fb3e as &$__tplBlock['forums']){ ?>
                                <tr class="forums">
                                    <td class="flag_read">
                                        <?php if (!$__tplBlock['forums']['IS_READ']) : ?><a href="markread-forums-<?php echo $__tplBlock['forums']['ID']; ?>.html"><?php endif; ?>
                                        <img src="./images/icones/forum_<?php echo $__tplBlock['forums']['ICON_PREFIX']; ?>read.png" <?php if ($__tplBlock['forums']['IS_READ']) : ?>alt="Vous avez lu tous les sujets de ce forum" title="Vous avez lu tous les sujets de ce forum"<?php else : ?>alt="Vous avez des sujets non lus dans ce forum" title="Vous avez des sujets non lus dans ce forum"<?php endif; ?> />
                                        <?php if (!$__tplBlock['forums']['IS_READ']) : ?></a><?php endif; ?>								
                                    </td>
                                    <td class="forums_name">
                                        <a href="<?php echo $__tplBlock['forums']['URL']; ?>"><?php echo $__tplBlock['forums']['NAME']; ?></a>
                                        <?php if ($__tplBlock['forums']['DESCRIPTION']) : ?><br /><?php echo $__tplBlock['forums']['DESCRIPTION'];  endif; ?>
                                        <?php if (isset($__tplBlock['forums']['subs'])) : $__tpl_3cc2b2880860bd86eb261d4ca75dcac96b303adb = &$__tplBlock['forums']['subs']; foreach ($__tpl_3cc2b2880860bd86eb261d4ca75dcac96b303adb as &$__tplBlock['subs']){ ?>
                                            <?php if ($__tplBlock['subs']['FIRST']) : ?><br /><img src="./images/icones/list_sub.png" /><?php endif; ?>
                                            <a href="<?php echo $__tplBlock['subs']['URL']; ?>"><?php echo $__tplBlock['subs']['NAME']; ?></a><?php if (!$__tplBlock['subs']['LAST']) : ?>, <?php endif; ?>
                                        <?php } endif; ?>
                                    </td>
                                    <td class="stats">
                                        <?php echo $__tplBlock['forums']['TOPICS']; ?>
                                    </td>
                                    <td class="stats">
                                        <?php echo $__tplBlock['forums']['POSTS']; ?>
                                    </td>
                                    <td class="last_answer">
                                        <?php echo $__tplBlock['forums']['LAST_TOPIC']; ?>
                                        <?php echo $__tplBlock['forums']['LAST_DATE']; ?>
                                        <?php echo $__tplBlock['forums']['LAST_USER']; ?>
                                    </td>
                                </tr>
                            <?php } endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <?php if ($tpl->vars['SHOW_TOPICS']) : ?>
                    <table class="topics_info">
                        <tbody>
                            <tr>
                                <td class="post_new_topics"><?php if ($tpl->vars['FORUM_OPENED']) : ?><a href="new-topic-<?php echo $tpl->vars['FORUM_ID']; ?>.html">Nouveau Sujet</a><?php else : ?>Forum Fermé<?php endif; ?></td>
                                <td class="right">
                                    <a href="<?php echo $tpl->vars['URL_MARKREAD']; ?>" class="markread">Marquer ce forum comme lu</a>

                                    <?php if ($tpl->vars['USE_PAGINATION']) : ?>
                                        <br /><div class="pagination">
                                        <?php if ($tpl->getBlock('pagination')) : $__tpl_513ce980ccebb55155db5309f5cdd1e7d8dd8960 = &$tpl->getBlock('pagination'); foreach ($__tpl_513ce980ccebb55155db5309f5cdd1e7d8dd8960 as &$__tplBlock['pagination']){ ?><span class="page"><?php echo $__tplBlock['pagination']['PAGE']; ?></span><?php } endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <table class="list_topics">
                        <thead>
                            <tr>
                            <th colspan="3" class="lbl_cat">Nom du Sujet</th>
                            <!--<th class="lbl_nbr">Pages</th>//-->
                            <th class="lbl_nbr">Lectures</th>
                            <th class="lbl_nbr">Réponses</th>
                            <th class="lbl_last">Dernière Réponse</th>
                            </tr>
                        </thead>

                        <tfoot>
                            <tr>
                            <th colspan="3" class="lbl_cat">Nom du Sujet</th>
                            <!--<th class="lbl_nbr">Pages</th>//-->
                            <th class="lbl_nbr">Lectures</th>
                            <th class="lbl_nbr">Réponses</th>
                            <th class="lbl_last">Dernière Réponse</th>
                            </tr>
                        </tfoot>

                        <tbody>
                            <?php if ($tpl->getBlock('topics')) : $__tpl_0b03bb922c3461dd166d143d2f82e32eadbd93b7 = &$tpl->getBlock('topics'); foreach ($__tpl_0b03bb922c3461dd166d143d2f82e32eadbd93b7 as &$__tplBlock['topics']){ ?>
                                <tr class="topics">
                                    <td class="flag_read"><img src="./images/icones/topic_<?php echo $__tplBlock['topics']['ICON_PREFIX']; ?>read.png" <?php if ($__tplBlock['topics']['IS_READ']) : ?>alt="Vous avez lu ce sujet" title="Vous avez lu ce sujet"<?php else : ?>alt="Vous n'avez pas lu ce sujet" title="Vous n'avez pas lu ce sujet"<?php endif; ?> /></td>
                                    <td class="flag_special">
                                        <?php if ($__tplBlock['topics']['IS_POSTIT']) : ?><img src="./images/icones/topic_postit.png" alt="Post-it" title="Ce sujet est un post-it" /><?php endif; ?>
                                        <?php if ($__tplBlock['topics']['IS_SOLVED']) : ?><img src="./images/icones/topic_solved.png" alt="Résolu" title="Ce sujet est résolu" /><?php endif; ?>
                                        <?php if ($__tplBlock['topics']['IS_LOCKED']) : ?><img src="./images/icones/topic_locked.png" alt="Fermé" title="Ce sujet est fermé" /><?php endif; ?>
                                    </td>
                                    <td class="topic_name">
                                        <?php echo $__tplBlock['topics']['LAST_READ'];  if ($__tplBlock['topics']['IS_POSTIT']) : ?><strong>[Post-It]</strong> <?php endif; ?><a href="<?php echo $__tplBlock['topics']['U_TITLE']; ?>" title="Créé <?php echo $__tplBlock['topics']['FIRST_TIME']; ?>, par <?php echo Talus_TPL_Filters::protect($__tplBlock['topics']['FIRST_USER']); ?>"><?php echo $__tplBlock['topics']['TITLE']; ?></a> [Pages : <?php if (isset($__tplBlock['topics']['pagination'])) : $__tpl_0e754ab71f494472ab1da48c1d64613e54465b50 = &$__tplBlock['topics']['pagination']; foreach ($__tpl_0e754ab71f494472ab1da48c1d64613e54465b50 as &$__tplBlock['pagination']){  echo $__tplBlock['pagination']['PAGE'];  if (!$__tplBlock['pagination']['LAST']) : ?>,<?php endif;  } endif; ?>]
                                        <?php if ($__tplBlock['topics']['DESCRIPTION']) : ?><br /><span class="topic_description"><?php echo $__tplBlock['topics']['DESCRIPTION']; ?></span><?php endif; ?>
                                    </td>
                                    <td class="stats"><?php echo $__tplBlock['topics']['VIEWS']; ?></td>
                                    <td class="stats"><?php echo $__tplBlock['topics']['REPLIES']; ?></td>
                                    <td class="last_answer">
                                        <?php echo $__tplBlock['topics']['LAST_POST']; ?>
                                        <?php echo $__tplBlock['topics']['LAST_USER']; ?>
                                    </td>
                                </tr>
                            <?php } else : if (true) { ?>
                                <tr class="topics">
                                    <td colspan="6" class="no_topics">Il n'y a pas de sujets pour le moment dans ce forum<?php if ($tpl->vars['FORUM_OPENED']) : ?> ; Vous pouvez en créer un si vous le désirez !<?php else : ?>...<?php endif; ?></td>
                                </tr>
                            <?php } endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>

                <table class="topics_info">
                    <tbody>
                        <tr>
                            <td class="post_new_topics"><?php if ($tpl->vars['FORUM_OPENED'] && $tpl->vars['SHOW_TOPICS']) : ?><a href="new-topic-<?php echo $tpl->vars['FORUM_ID']; ?>.html">Nouveau Sujet</a><?php else : ?>Forum Fermé<?php endif; ?></td>
                            <td class="right">
                                <a href="<?php echo $tpl->vars['URL_MARKREAD']; ?>" class="markread">Marquer ce forum comme lu</a>
                                <?php if ($tpl->vars['USE_PAGINATION']) : ?><br /><div class="pagination"><?php if ($tpl->getBlock('pagination')) : $__tpl_97c23e8c2511da56fe4153ab516e2a8358d59cfd = &$tpl->getBlock('pagination'); foreach ($__tpl_97c23e8c2511da56fe4153ab516e2a8358d59cfd as &$__tplBlock['pagination']){ ?><span class="page"><?php echo $__tplBlock['pagination']['PAGE']; ?></span><?php } endif; ?></div><?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
<?php $tpl->includeTpl('foot.html', false, 0); ?>