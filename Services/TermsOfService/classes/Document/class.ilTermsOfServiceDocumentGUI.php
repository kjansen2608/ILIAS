<?php
/* Copyright (c) 1998-2018 ILIAS open source, Extended GPL, see docs/LICENSE */

use ILIAS\FileUpload\FileUpload;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ilTermsOfServiceDocumentGUI
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilTermsOfServiceDocumentGUI implements \ilTermsOfServiceControllerEnabled
{
	/** @var \ilObjTermsOfService */
	protected $tos;

	/** @var \ilTemplate */
	protected $tpl;

	/** @var \ilCtrl */
	protected $ctrl;

	/** @var \ilLanguage */
	protected $lng;

	/** @var \ilRbacSystem */
	protected $rbacsystem;

	/** @var \ilErrorHandling */
	protected $error;

	/** @var \ilObjUser */
	protected $user;

	/** @var \ilLogger */
	protected $log;

	/** @var ILIAS\UI\Factory */
	protected $uiFactory;

	/** @var ILIAS\UI\Renderer */
	protected $uiRenderer;

	/** @var ServerRequestInterface */
	protected $request;

	/** @var \ilToolbarGUI */
	protected $toolbar;

	/** @var FileUpload */
	protected $fileUpload;

	/**
	 * ilTermsOfServiceDocumentGUI constructor.
	 * @param \ilObjTermsOfService $tos
	 * @param \ilTemplate $tpl
	 * @param \ilObjUser $user
	 * @param \ilCtrl $ctrl
	 * @param \ilLanguage $lng
	 * @param \ilRbacSystem $rbacsystem
	 * @param \ilErrorHandling $error
	 * @param \ilLogger $log
	 * @param \ilToolbarGUI $toolbar
	 * @param ServerRequestInterface $request
	 * @param \ILIAS\UI\Factory $uiFactory
	 * @param \ILIAS\UI\Renderer $uiRenderer
	 * @param FileUpload $fileUpload
	 */
	public function __construct(
		\ilObjTermsOfService $tos,
		\ilTemplate $tpl,
		\ilObjUser $user,
		\ilCtrl $ctrl,
		\ilLanguage $lng,
		\ilRbacSystem $rbacsystem,
		\ilErrorHandling $error,
		\ilLogger $log,
		\ilToolbarGUI $toolbar,
		ServerRequestInterface $request,
		ILIAS\UI\Factory $uiFactory,
		ILIAS\UI\Renderer $uiRenderer,
		FileUpload $fileUpload
	)
	{
		$this->tos = $tos;
		$this->tpl = $tpl;
		$this->ctrl = $ctrl;
		$this->lng = $lng;
		$this->rbacsystem = $rbacsystem;
		$this->error = $error;
		$this->user = $user;
		$this->log = $log;
		$this->toolbar = $toolbar;
		$this->request  = $request;
		$this->uiFactory  = $uiFactory;
		$this->uiRenderer = $uiRenderer;
		$this->fileUpload = $fileUpload;
	}

	/**
	 * 
	 */
	public function executeCommand()
	{
		$nextClass = $this->ctrl->getNextClass($this);
		$cmd       = $this->ctrl->getCmd();

		if (!$this->rbacsystem->checkAccess('read', $this->tos->getRefId())) {
			$this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
		}

		switch (strtolower($nextClass)) {
			default:
				if ($cmd == '' || !method_exists($this, $cmd)) {
					$cmd = 'showDocuments';
				}
				$this->$cmd();
				break;
		}
	}

	/**
	 *
	 */
	protected function confirmReset()
	{
		if (!$this->rbacsystem->checkAccess('write', $this->tos->getRefId())) {
			$this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
		}

		$confirmation = new \ilConfirmationGUI();
		$confirmation->setFormAction($this->ctrl->getFormAction($this, 'confirmReset'));
		$confirmation->setConfirm($this->lng->txt('confirm'), 'reset');
		$confirmation->setCancel($this->lng->txt('cancel'), 'showDocuments');
		$confirmation->setHeaderText($this->lng->txt('tos_sure_reset_tos'));

		$this->tpl->setContent($confirmation->getHTML());
	}

	/**
	 *
	 */
	protected function reset()
	{
		if (!$this->rbacsystem->checkAccess('write', $this->tos->getRefId())) {
			$this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
		}

		$this->tos->resetAll();

		$this->log->info('Terms of service reset by ' . $this->user->getId() . ' [' . $this->user->getLogin() . ']');
		\ilUtil::sendSuccess($this->lng->txt('tos_reset_successful'));

		$this->showDocuments();
	}

	/**
	 *
	 */
	protected function showDocuments()
	{
		$addDocumentBtn = \ilLinkButton::getInstance();
		$addDocumentBtn->setPrimary(true);
		$addDocumentBtn->setUrl($this->ctrl->getLinkTarget($this, 'showAddDocumentForm'));
		$addDocumentBtn->setCaption('tos_add_document_btn_label');
		$this->toolbar->addStickyItem($addDocumentBtn);

		$documentTableGui = new \ilTermsOfServiceDocumentTableGUI(
			$this,
			'showDocuments',
			$this->uiFactory,
			$this->uiRenderer
		);
		$documentTableGui->setProvider(new ilTermsOfServiceDocumentTableDataProvider());
		$documentTableGui->populate();

		$this->tpl->setVariable('MESSAGE', $this->getResetMessageBoxHtml());
		$this->tpl->setContent($documentTableGui->getHTML());
	}

	/**
	 * @return string
	 */
	protected function getResetMessageBoxHtml(): string
	{
		if ($this->tos->getLastResetDate() && $this->tos->getLastResetDate()->get(IL_CAL_UNIX) != 0) {
			$status = \ilDatePresentation::useRelativeDates();
			\ilDatePresentation::setUseRelativeDates(false);
			$resetText = sprintf(
				$this->lng->txt('tos_last_reset_date'),
				\ilDatePresentation::formatDate($this->tos->getLastResetDate())
			);
			\ilDatePresentation::setUseRelativeDates($status);
		} else {
			$resetText = $this->lng->txt('tos_never_reset');
		}

		$buttons = [];
		if ($this->rbacsystem->checkAccess('write', $this->tos->getRefId())) {
			$buttons = [
				$this->uiFactory
					->button()
					->standard($this->lng->txt('tos_reset_tos_for_all_users'), $this->ctrl->getLinkTarget($this, 'confirmReset'))
			];
		}

		return $this->uiRenderer->render(
			$this->uiFactory->messageBox()
				->info($resetText)
				->withButtons($buttons)
		);
	}

	/**
	 * @param ilTermsOfServiceDocument $document
	 * @return ilTermsOfServiceDocumentFormGUI
	 */
	protected function getDocumentForm(ilTermsOfServiceDocument $document): \ilTermsOfServiceDocumentFormGUI
	{
		if ($document->getId() > 0) {
			$this->ctrl->setParameter($this, 'tos_id', $document->getId());
		}

		$form = new \ilTermsOfServiceDocumentFormGUI(
			$document,
			$this->user,
			$this->fileUpload,
			$this->ctrl->getFormAction($this, 'saveAddDocumentForm'),
			'saveAddDocumentForm',
			'showDocuments',
			$this->rbacsystem->checkAccess('write', $this->tos->getRefId())
		);

		return $form;
	}

	/**
	 *
	 */
	protected function saveAddDocumentForm()
	{
		if (!$this->rbacsystem->checkAccess('write', $this->tos->getRefId())) {
			$this->error->raiseError($this->lng->txt('permission_denied'), $this->error->MESSAGE);
		}

		$form = $this->getDocumentForm(new ilTermsOfServiceDocument());
		if ($form->saveObject()) {
			\ilUtil::sendSuccess($this->lng->txt('saved_successfully'), true);
			$this->ctrl->redirect($this, 'settings');
		} else if ($form->hasTranslatedError()) {
			\ilUtil::sendFailure($form->getTranslatedError());
		}

		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * 
	 */
	protected function showAddDocumentForm()
	{
		$form = $this->getDocumentForm(new ilTermsOfServiceDocument());
		$this->tpl->setContent($form->getHTML());
	}

	/**
	 * 
	 */
	protected function showEditDocumentForm()
	{
		$form = $this->getDocumentForm(new ilTermsOfServiceDocument());
		$this->tpl->setContent($form->getHTML());

		// TODO
	}

	/**
	 *
	 */
	protected function saveEditDocumentForm()
	{
		// TODO
	}

	/**
	 *
	 */
	protected function showDocumentDeletionConfirmation()
	{
		//tos#:#tos_sure_delete_documents_s#:#Are you sure you want to delete the document?
		//tos#:#tos_sure_delete_documents_p#:#Are you sure you want to delete the selected documents?
		
	}

	/**
	 *
	 */
	protected function deleteDocuments()
	{
		// TODO
		//tos#:#tos_deleted_documents_s#:#The document has been deleted.
		//tos#:#tos_deleted_documents_p#:#The documents have been deleted.
	}

	protected function saveDocumentSorting()
	{
		
	}



	/**
	 *
	 */
	protected function showCriteria()
	{
		// TODO
	}
}