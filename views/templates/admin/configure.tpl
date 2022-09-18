{**
 * License limited to a single site, for use on another site please purchase a license for this module.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @author    Dingedi.com
 * @copyright Copyright 2022 © Dingedi All right reserved
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * @category  Dingedi PrestaShop Modules
 *}
<script type="text/javascript">
    if (typeof getPush !== "undefined") {
        getPush = () => null;
    }
</script>
<div id="dingedimodule-container">
    <div class="clearfix">
        <div class="panel">
            {if $dgtranslationall_page !== 'index'}
                <a href="{$dgtranslationall_default_link}&dgtranslationallpage=index" class="btn btn-secondary mb-4">
                    <span class="icon-arrow-circle-left"></span>
                    {l s='Back to home page' mod='dgtranslationall'}
                </a>
            {/if}
            {if $dgtranslationall_page === 'index'}
                <div class="row">
                    <div class="col-md-push-3 col-md-6">
                        <h2 class="text-center">{l s='What content would you like to translate?' mod='dgtranslationall'}</h2>
                        <div class="row">
                            <div class="col-md-4 d-flex flex-column justify-content-center align-items-center"
                                 style="min-height: 200px">
                                <h3 class="mt-3">{l s='Content' mod='dgtranslationall'}</h3>
                                <a href="{$dgtranslationall_default_link}&dgtranslationallpage=content"
                                   class="btn btn-outline-primary btn-lg" style="padding: 60px">
                                    <span class="material-icons" style="font-size: 40px;">translate</span>
                                </a>
                            </div>
                            <div class="col-md-4 d-flex flex-column justify-content-center align-items-center"
                                 style="min-height: 200px">
                                <h3 class="mt-3">{l s='Modules' mod='dgtranslationall'}</h3>
                                <a href="{$dgtranslationall_default_link}&dgtranslationallpage=modules"
                                   class="btn btn-outline-primary btn-lg" style="padding: 60px">
                                    <span class="icon-puzzle-piece" style="font-size: 40px;"></span>
                                </a>
                            </div>
                            <div class="col-md-4 d-flex flex-column justify-content-center align-items-center"
                                 style="min-height: 200px">
                                <h3 class="mt-3">{l s='Themes and Emails' mod='dgtranslationall'}</h3>
                                <a href="{$dgtranslationall_default_link}&dgtranslationallpage=themes"
                                   class="btn btn-outline-primary btn-lg" style="padding: 60px">
                                    <span class="icon-desktop" style="font-size: 40px;"></span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            {elseif $dgtranslationall_page === 'content'}
                <div id="dingedi-cttr-app" class="pb-4"></div>
            {elseif $dgtranslationall_page === 'modules'}
                <div id="dingedi-mttr-app" class="pb-4"></div>
            {elseif $dgtranslationall_page === 'themes'}
                <div id="dingedi-thtr-app" class="pb-4"></div>
            {/if}
        </div>
    </div>
</div>
