<?php

/**

 * Please view attribution.php for detailed licensing info on all files within this directory

 **/
class OW_MobileApplication extends OW_Application
{
    use OW_Singleton;

    /**
     * Constructor.
     */
    private function __construct()
    {
        $this->context = self::CONTEXT_MOBILE;
    }

    /**
     * ---------
     */
    public function handleRequest()
    {
        $baseConfigs = OW::getConfig()->getValues('base');

        //members only
        if ( (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_CANT_VIEW && !OW::getUser()->isAuthenticated() )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_User',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'standardSignIn'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.members_only', $attributes);
            $this->addCatchAllRequestsException('base.members_only_exceptions', 'base.members_only');
        }

        //splash screen
        if ( (bool) OW::getConfig()->getValue('base', 'splash_screen') && !isset($_COOKIE['splashScreen']) )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'splashScreen',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_REDIRECT => true,
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_JS => true,
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ROUTE => 'base_page_splash_screen'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.splash_screen', $attributes);
            $this->addCatchAllRequestsException('base.splash_screen_exceptions', 'base.splash_screen');
        }

        // password protected
        if ( (int) $baseConfigs['guests_can_view'] === BOL_UserService::PERMISSIONS_GUESTS_PASSWORD_VIEW && !OW::getUser()->isAuthenticated() && !isset($_COOKIE['base_password_protection'])
        )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'passwordProtection'
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.password_protected', $attributes);
            $this->addCatchAllRequestsException('base.password_protected_exceptions', 'base.password_protected');
        }

        // maintenance mode
        if ( (bool) $baseConfigs['maintenance'] && !OW::getUser()->isAdmin() )
        {
            $attributes = array(
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_CTRL => 'BASE_MCTRL_BaseDocument',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_ACTION => 'maintenance',
                OW_RequestHandler::CATCH_ALL_REQUEST_KEY_REDIRECT => true
            );

            OW::getRequestHandler()->setCatchAllRequestsAttributes('base.maintenance_mode', $attributes);
            $this->addCatchAllRequestsException('base.maintenance_mode_exceptions', 'base.maintenance_mode');
        }


        try
        {
            OW::getRequestHandler()->dispatch();
        }
        catch ( RedirectException $e )
        {
            $this->redirect($e->getUrl(), $e->getRedirectCode());
        }
        catch ( InterceptException $e )
        {
            OW::getRequestHandler()->setHandlerAttributes($e->getHandlerAttrs());
            $this->handleRequest();
        }
    }

    /**
     * Method called just before request responding.
     */
    public function finalize()
    {
        $document = OW::getDocument();

        $meassages = OW::getFeedback()->getFeedback();

        foreach ( $meassages as $messageType => $messageList )
        {
            foreach ( $messageList as $message )
            {
                $document->addOnloadScript("OWM.message(" . json_encode($message) . ", '" . $messageType . "');");
            }
        }

        $event = new OW_Event(OW_EventManager::ON_FINALIZE);
        OW::getEventManager()->trigger($event);
    }

    /**
     * System method. Don't call it!!!
     */
    public function onBeforeDocumentRender()
    {
        $document = OW::getDocument();

        $document->addStyleSheet(OW::getPluginManager()->getPlugin('base')->getStaticCssUrl() . 'mobile.css' . '?' . OW::getConfig()->getValue('base',
                'cachedEntitiesPostfix'), 'all', -100);
        $document->addStyleSheet(OW::getThemeManager()->getCssFileUrl(true) . '?' . OW::getConfig()->getValue('base',
                'cachedEntitiesPostfix'), 'all', (-90));

        if ( OW::getThemeManager()->getCurrentTheme()->getDto()->getCustomCssFileName() !== null )
        {
            $document->addStyleSheet(OW::getThemeManager()->getThemeService()->getCustomCssFileUrl(OW::getThemeManager()->getCurrentTheme()->getDto()->getKey(),
                    true));
        }

        $language = OW::getLanguage();

        if ( $document->getTitle() === null )
        {
            $document->setTitle($language->text('mobile', 'page_default_title'));
        }

        if ( $document->getDescription() === null )
        {
            $document->setDescription($language->text('mobile', 'page_default_description'));
        }

        if ( $document->getHeading() === null )
        {
            $document->setHeading($language->text('mobile', 'page_default_heading'));
        }

        $document->addMetaInfo('viewport', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }

    public function activateMenuItem()
    {
        
    }

    protected function newDocument()
    {
        $language = BOL_LanguageService::getInstance()->getCurrent();
        $document = new OW_HtmlDocument();
        $document->setTemplate(OW::getThemeManager()->getMasterPageTemplate('mobile_html_document'));
        $document->setCharset('UTF-8');
        $document->setMime('text/html');
        $document->setLanguage($language->getTag());

        if ( $language->getRtl() )
        {
            $document->setDirection('rtl');
        }
        else
        {
            $document->setDirection('ltr');
        }

        if ( (bool) OW::getConfig()->getValue('base', 'favicon') )
        {
            $document->setFavicon(OW::getPluginManager()->getPlugin('base')->getUserFilesUrl() . 'favicon.ico');
        }

        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery.min.js',
            'text/javascript', (-100));
        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'mobile.js?' . OW::getConfig()->getValue('base',
                'cachedEntitiesPostfix'), 'text/javascript', (-50));
        OW::getEventManager()->bind(OW_EventManager::ON_AFTER_REQUEST_HANDLE, array($this, 'onBeforeDocumentRender'));

        return $document;
    }

    protected function initRequestHandler()
    {
        OW::getRequestHandler()->setStaticPageAttributes('BASE_MCTRL_BaseDocument', 'staticDocument');
    }

    protected function findAllStaticDocs()
    {
        return BOL_NavigationService::getInstance()->findAllMobileStaticDocuments();
    }

    protected function findFirstMenuItem( $availableFor )
    {
        return BOL_NavigationService::getInstance()->findFirstLocal($availableFor, OW_Navigation::MOBILE_TOP);
    }

    protected function getSiteRootRoute()
    {
        return new OW_Route('base_default_index', '/', 'BASE_MCTRL_WidgetPanel', 'index');
    }

    protected function getMasterPage()
    {
        return new OW_MobileMasterPage();
    }
}
