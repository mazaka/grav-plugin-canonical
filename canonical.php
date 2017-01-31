<?php
namespace Grav\Plugin;

use Grav\Common\Page\Page;
use Grav\Common\Plugin;
use Grav\Common\Uri;

/**
 * Class CanonicalPlugin
 * @package Grav\Plugin
 */
class CanonicalPlugin extends Plugin
{
    /**
     * @return array
     *
     * The getSubscribedEvents() gives the core a list of events
     *     that the plugin wants to listen to. The key of each
     *     array section is the event that the plugin listens to
     *     and the value (in the form of an array) contains the
     *     callable (or function) as well as the priority. The
     *     higher the number the higher the priority.
     */
    public static function getSubscribedEvents()
    {
        return [
            'onPluginsInitialized' => ['onPluginsInitialized', 0]
        ];
    }

    /**
     * Initialize the plugin
     */
    public function onPluginsInitialized()
    {
        // Don't proceed if we are in the admin plugin
        if ($this->isAdmin()) {
            return;
        }

        $this->enable([
            'onTwigSiteVariables'     => ['onTwigSiteVariables', 0],
        ]);
    }


    /**
     * Add Twig function
     */
    public function onTwigSiteVariables()
    {
        $this->grav['twig']->twig_vars['canonical'] = $this->generateCanonical();
    }


    private function getTranslatedPageUrls(Page $page)
    {
        $translated = $page->translatedLanguages();

        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        $rootUrl = $uri->base();

        $urls = array();
        foreach ($translated as $lang => $slug) {
            $urls[$lang] = array(
                'root' => $rootUrl.'/'.$lang,
                'params' => array()
            );
        }

        $isHome = false;
        while ($isHome == false) {
            $translations = $page->translatedLanguages();

            foreach ($translations as $lang => $slug) {
                if (isset($urls[$lang]) && !$page->home()) {
                    array_unshift($urls[$lang]['params'], $slug);
                }
            }
            $page = $page->parent();
            if ($page instanceof Page) {
                $isHome = $page->home();
            } else {
                $isHome = true;
            }
        }

        foreach ($urls as $language => $u) {
            $url = $u['root'];
            $params = join('/', $u['params']);
            if (strlen($params)) {
                $url .= '/'.$params;
            }

            $urls[$language] = $url;
        }

        return $urls;
    }


    private function generateCanonical()
    {
        $enabled = $this->config->get('plugins.canonical.enabled');

        $page = $this->grav['page'];

        if (!$page || !$enabled) {
            return '';
        }

        $currentLang = $page->language();

        $canonical = '<link rel="canonical" href="' .$page->url(true, true). '" />
';

        foreach ($this->getTranslatedPageUrls($page) as $language => $url) {
            if ($language != $currentLang) {
                $canonical .= '<link rel="alternate" hreflang="'.$language.'" href="' .$url. '" />
';
            }
        }

        return $canonical;
    }

}
