<?php

namespace C4Y\Block4you\Backend;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Input;
use Contao\System;
use C4Y\Block4you\Service\ElementSetService;

class ElementSetBackend extends Backend
{
    public function run()
    {
        $this->import('BackendUser', 'User');

        // Check permissions
        if (!$this->User->hasAccess('article', 'modules')) {
            $this->log('Not enough permissions to access element sets', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $action = Input::get('action');
        
        switch ($action) {
            case 'modal':
                $this->showModal();
                break;
            case 'preview':
                $this->showPreview();
                break;
            case 'insert':
                $this->insertElementSet();
                break;
            default:
                $this->showModal();
        }
    }

    private function showModal(): void
    {
        $elementSetService = System::getContainer()->get(ElementSetService::class);
        
        $template = new BackendTemplate('be_element_sets_modal');
        $template->elementSets = $elementSetService->getAvailableElementSets();
        $template->articleId = Input::get('id');
        
        echo $template->parse();
    }

    private function showPreview(): void
    {
        $elementSetService = System::getContainer()->get(ElementSetService::class);
        $elementSetId = Input::get('elementSet');
        
        try {
            $preview = $elementSetService->getElementSetPreview($elementSetId);
            header('Content-Type: application/json');
            echo json_encode($preview);
        } catch (\Exception $e) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function insertElementSet(): void
    {
        $elementSetService = System::getContainer()->get(ElementSetService::class);
        $articleId = Input::get('articleId');
        $elementSetId = Input::get('elementSet');
        $position = Input::get('position');
        
        header('Content-Type: application/json');
        
        try {
            $result = $elementSetService->insertElementSet($articleId, $elementSetId, $position);
            echo json_encode(['success' => true, 'message' => 'Element-Set erfolgreich eingefügt']);
        } catch (\Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
