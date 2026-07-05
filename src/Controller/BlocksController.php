<?php

namespace C4Y\Blocks4you\Controller;

use Contao\CoreBundle\Controller\AbstractBackendController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use C4Y\Blocks4you\Service\BlockService;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Twig\Environment;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[Route('/contao/blocks', name: 'contao_blocks', defaults: ['_scope' => 'backend'])]
class BlocksController extends AbstractBackendController
{
    private BlockService $blockService;
    private ContaoCsrfTokenManager $csrfTokenManager;
    private Environment $twig;
    private ParameterBagInterface $parameterBag;

    public function __construct(
        BlockService $blockService, 
        ContaoCsrfTokenManager $csrfTokenManager,
        Environment $twig,
        ParameterBagInterface $parameterBag
    ) {
        $this->blockService = $blockService;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->twig = $twig;
        $this->parameterBag = $parameterBag;
    }

    public function __invoke(Request $request): Response
    {
        $GLOBALS['TL_CSS'][] = '/bundles/blocks4you/css/blocks.css';
        
        $blockId = $request->query->get('blockId');
        $doBlockPaste = $request->query->get('doBlockPaste');

        if ($blockId && $doBlockPaste) {
            return $this->insertBlock($request);
        }
        
        return $this->showBlocks($request);
    }

    protected function showBlocks(Request $request) {
        $articleId = $request->query->get('id');
        $pid = $request->query->get('pid');
        $table = $request->query->get('table');
        $do = $request->query->get('do');
        $mode = $request->query->get('mode');

        if (!$articleId) {
            return $this->render('@Blocks4you/blocks.html.twig', [
                'error' => 'Keine Artikel-ID angegeben',
                'title' => 'Block auswählen',
                'headline' => 'Fehler',
                'blocks' => []
            ]);
        }

        try {
            $blocks = $this->blockService->getAvailableBlocks();
            
            // Get the default request token (like Contao does internally)
            $requestToken = $this->csrfTokenManager->getDefaultTokenValue();
            
            // Prepare back URL with request token
            $backUrl = $request->headers->get('referer', '/contao?do=article&table=tl_content&id=' . $articleId);
            $backUrl .= (strpos($backUrl, '?') !== false ? '&' : '?') . 'rt=' . $requestToken;
            
            return $this->render('@Blocks4you/blocks.html.twig', [
                'title' => 'Block auswählen',
                'headline' => 'Block für Artikel ' . $articleId . ' auswählen',
                'blocks' => $blocks,
                'table' => $table,
                'do' => $do,
                'articleId' => $articleId,
                'pid' => $pid,
                'mode' => $mode,
                'requestToken' => $requestToken,
                'backUrl' => $backUrl
            ]);
            
        } catch (\Exception $e) {
            return $this->render('@Blocks4you/blocks.html.twig', [
                'error' => 'Fehler beim Laden der Blöcke: ' . $e->getMessage(),
                'title' => 'Block auswählen',
                'headline' => 'Fehler',
                'blocks' => []
            ]);
        }
    }

    protected function insertBlock(Request $request) {
        $articleId = $request->query->get('id');
        $afterId = $request->query->get('pid'); // Block ID after which to insert (from position selection)
        $blockId = $request->query->get('blockId');
        $table = $request->query->get('table');
        $mode = (int)$request->query->get('mode', 1); // Default mode = 1

        if (!$articleId) {
            return;
        }

        try {
            if ($mode === 2) {
                // Mode 2: Insert at first position (position = 1)
                $position = 1;
            } else {
                // Mode 1: Calculate insertion position based on selected element
                $position = $this->calculateInsertPosition((int)$articleId, (int)$afterId);
            }
            
            // Insert the element set
            $this->blockService->insertBlockAtPosition((int)$articleId, $blockId, $position);

            // empty Clipboard
            $objSession = $request->getSession();
            $arrClipboard = $objSession->get('CLIPBOARD');
            unset($arrClipboard[$table]);
            $objSession->set('CLIPBOARD', $arrClipboard);

            
            // Redirect back to content view
            $redirectUrl = '/contao?do=article&table=' . $table . '&id=' . $articleId . '&t=' . time();
            return new RedirectResponse($redirectUrl);
            
        } catch (\Exception $e) {
            // Add error message and continue
            \Contao\Message::addError('Fehler beim Einfügen des Blocks: ' . $e->getMessage());
        }
    }

    private function calculateInsertPosition(int $articleId, int $afterId): int
    {
        if (!$afterId) {
            // Insert at the beginning
            return 0;
        }

        // Get the sorting of the reference element
        $db = \Contao\System::getContainer()->get('database_connection');
        $result = $db->fetchAssociative(
            'SELECT sorting FROM tl_content WHERE id = ? AND pid = ?',
            [$afterId, $articleId]
        );

        if ($result) {
            // Insert after the reference element
            return (int)$result['sorting'] + 64;
        }

        return 128;
    }
}
