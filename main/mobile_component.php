<?php

/**

 * Please view attribution.php for detailed licensing info on all files within this directory

 **/
abstract class OW_MobileComponent extends OW_Component
{

    /**
     * Constructor.
     *
     * @param string $template
     */
    public function __construct()
    {
    }

    public function render()
    {
        if ( $this->getTemplate() === null )
        {
            try
            {
                $plugin = OW::getPluginManager()->getPlugin(OW::getAutoloader()->getPluginKey(get_class($this)));
            }
            catch ( InvalidArgumentException $e )
            {
                $plugin = null;
            }

            if ( $plugin !== null )
            {
                $template = OW::getAutoloader()->classToFilename(get_class($this), false);
                $this->setTemplate($plugin->getMobileCmpViewDir() . $template . '.html, .shtml, .htm');
            }
        }

        return parent::render();
    }
}
