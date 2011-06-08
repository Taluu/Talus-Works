<?php $tpl->includeTpl('head.html', false, 0); ?>
                <h2>Exploration de Talus' Works (Repertoire courant : <?php echo $tpl->vars['CURRENT']; ?>)</h2>
                
                <?php if ($tpl->vars['CURRENT'] == '~/') : ?>
                    <p>Bienvenue sur l'explorateur de Sources de Talus' Works ! Talus' Works étant un site <strong>entièrement</strong> open-source, j'estime ainsi que vous avez le droit de pouvoir vous balader dans toutes ses sources (sauf celles protégées... :p).</p>
                <?php endif; ?>
                
                <table class="dirlist">
                    <thead>
                        <tr>
                            <th class="arbo_name" colspan="2"><a href="<?php echo $tpl->vars['U_NAME']; ?>">Nom</a></th>
                            <th><a href="<?php echo $tpl->vars['U_TYPE']; ?>">Type</a></th>
                            <th><a href="<?php echo $tpl->vars['U_FILESIZE']; ?>">Taille</a></th>
                            <th><a href="<?php echo $tpl->vars['U_LASTMODIF']; ?>">Dernière modification</a></th>
                        </tr>
                    </thead>
                    
                    <tfoot>
                        <tr>
                            <th class="arbo_name" colspan="2"><a href="<?php echo $tpl->vars['U_NAME']; ?>">Nom</a></th>
                            <th><a href="<?php echo $tpl->vars['U_TYPE']; ?>">Type</a></th>
                            <th><a href="<?php echo $tpl->vars['U_FILESIZE']; ?>">Taille</a></th>
                            <th><a href="<?php echo $tpl->vars['U_LASTMODIF']; ?>">Dernière modification</a></th>
                        </tr>
                    </tfoot>
                    
                    <tbody>
                        <?php if ($tpl->getBlock('dirlist')) : $__tpl_89f2f9245d04e888fef32003e045679b60bcb863 = &$tpl->getBlock('dirlist'); foreach ($__tpl_89f2f9245d04e888fef32003e045679b60bcb863 as &$__tplBlock['dirlist']){ ?>
                            
                            <tr>
                                <td class="arbo_imgtype"><img src="<?php echo $__tplBlock['dirlist']['IMGTYPE']; ?>" /></td>
                                <td class="arbo_name"><a href="<?php echo $__tplBlock['dirlist']['URL']; ?>"><?php echo $__tplBlock['dirlist']['NAME']; ?></a></td>
                                <td class="arbo_type"><?php echo $__tplBlock['dirlist']['TYPE']; ?></td>
                                <td class="arbo_size"><?php echo $__tplBlock['dirlist']['SIZE']; ?></td>
                                <td class="arbo_lastmodif"><?php echo $__tplBlock['dirlist']['LASTMODIF']; ?></td>
                            </tr>
                        <?php } endif; ?>
                    </tbody>
                </table>
<?php $tpl->includeTpl('foot.html', false, 0); ?>