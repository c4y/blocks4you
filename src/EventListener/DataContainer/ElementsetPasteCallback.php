<?php

namespace C4Y\Block4you\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use C4Y\Block4you\Service\ElementSetService;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class ElementsetPasteCallback
{
    private RequestStack $requestStack;
    private ElementSetService $elementSetService;
    private ContaoCsrfTokenManager $csrfTokenManager;

    public function __construct(
        RequestStack $requestStack, 
        ElementSetService $elementSetService,
        ContaoCsrfTokenManager $csrfTokenManager
    ) {
        $this->requestStack = $requestStack;
        $this->elementSetService = $elementSetService;
        $this->csrfTokenManager = $csrfTokenManager;
    }

    public function __invoke(DataContainer|null $dc = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $act = $request->query->get('act');
        $mode = $request->query->get('mode');
        $showElementSet = $request->query->get('showElementSet');
        $table = $request->query->get('table');
        $do = $request->query->get('do');
        $id = $request->query->get('id');

        if ($act === 'create' && $showElementSet === '1') {

            $pid = $request->query->get('pid');
            
            if (!$id) {
                \Contao\Message::addError('Keine Artikel-ID angegeben');
                return;
            }

            // Redirect to ElementsetController via custom backend route
            $redirectUrl = '/contao/elementset?do=' . urlencode($do) . '&table=' . urlencode($table). '&id=' . urlencode($id) . '&pid=' . $pid . '&mode=' . $mode;
            header('Location: ' . $redirectUrl);
            exit;
        }

        
    }

}
