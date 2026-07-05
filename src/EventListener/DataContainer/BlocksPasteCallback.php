<?php

namespace C4Y\Blocks4you\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use C4Y\Blocks4you\Service\BlockService;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;

#[AsCallback(table: 'tl_content', target: 'config.onload')]
class BlocksPasteCallback
{
    private RequestStack $requestStack;
    private BlockService $blockService;
    private ContaoCsrfTokenManager $csrfTokenManager;

    public function __construct(
        RequestStack $requestStack, 
        BlockService $blockService,
        ContaoCsrfTokenManager $csrfTokenManager
    ) {
        $this->requestStack = $requestStack;
        $this->blockService = $blockService;
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
        $showBlocks = $request->query->get('showBlocks');
        $table = $request->query->get('table');
        $do = $request->query->get('do');
        $id = $request->query->get('id');

        if ($act === 'create' && $showBlocks === '1') {

            $pid = $request->query->get('pid');
            
            if (!$id) {
                \Contao\Message::addError('Keine Artikel-ID angegeben');
                return;
            }

            // Redirect to ElementsetController via custom backend route
            $redirectUrl = '/contao/blocks?do=' . urlencode($do) . '&table=' . urlencode($table). '&id=' . urlencode($id) . '&pid=' . $pid . '&mode=' . $mode;
            header('Location: ' . $redirectUrl);
            exit;
        }

        
    }

}
