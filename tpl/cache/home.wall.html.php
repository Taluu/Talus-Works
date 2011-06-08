<?php $tpl->includeTpl('head.html', false, 0); ?>
                    <div class="code_overall">
                        <span class="code_top"><?php echo $tpl->vars['FILE']; ?></span>
                        <code class="code_main code_<?php echo $tpl->vars['TYPE']; ?>"><?php echo $tpl->vars['SOURCE_CODE']; ?></code>
                    </div>
                    
                    <?php if ($tpl->vars['SOURCE_CODE_COMPILED'] != '') : ?>
                        <div class="code_overall">
                            <span class="code_top"><?php echo $tpl->vars['FILE']; ?> : Fichier Compilé par Talus' TPL</span>
                            <code class="code_main code_php"><?php echo $tpl->vars['SOURCE_CODE_COMPILED']; ?></code>
                        </div>
                    <?php endif; ?>
<?php $tpl->includeTpl('foot.html', false, 0); ?>