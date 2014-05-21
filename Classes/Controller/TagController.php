<?php
namespace TYPO3\Tagme\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Bingquan Bao <bingquan.bao@gmail.com>, BBQ
 *  
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 *
 *
 * @package tagme
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 *
 */
class TagController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * tagRepository
	 *
	 * @var \TYPO3\Tagme\Domain\Repository\TagRepository
	 * @inject
	 */
	protected $tagRepository;

	/**
	 * action list
	 *
	 * @return void
	 */
	public function listAction() {
		$tags = $this->tagRepository->findAll();
		$this->view->assign('tags', $tags);
	}

	/**
	 * action show
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $tag
	 * @return void
	 */
	public function showAction(\TYPO3\Tagme\Domain\Model\Tag $tag) {
        //update counter of tag
        $tag->setCounter($tag->getCounter() + 1);
        $this->tagRepository->update($tag);
        $before = strtotime("-10 days");
        //var_dump($before);die;

        $url = "http://api.tumblr.com/v2/tagged?tag=tagkey&api_key=FWLNCaW9vTiZtthwrlU75oQzuUxz8kwLfpJsEavQgvryGGlVb8&limit=20&offset=2";
        $dataHandler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance("\TYPO3\Tagme\Service\DataHandler",$url,$tag->getName(),array('tagkey'=>$tag->getName()));
        if(0)
        $dataHandler->saveCache();

        $xml =  $dataHandler->readXml();

		$this->view->assign('tag', $tag);
		$this->view->assign('posts', $xml);
	}

	/**
	 * action new
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $newTag
	 * @dontvalidate $newTag
	 * @return void
	 */
	public function newAction(\TYPO3\Tagme\Domain\Model\Tag $newTag = NULL) {
		$this->view->assign('newTag', $newTag);
	}

	/**
	 * action create
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $newTag
	 * @return void
	 */
	public function createAction(\TYPO3\Tagme\Domain\Model\Tag $newTag) {
		$this->tagRepository->add($newTag);
		$this->flashMessageContainer->add('Your new Tag was created.');
		$this->redirect('list');
	}

	/**
	 * action edit
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $tag
	 * @return void
	 */
	public function editAction(\TYPO3\Tagme\Domain\Model\Tag $tag) {
		$this->view->assign('tag', $tag);
	}

	/**
	 * action update
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $tag
	 * @return void
	 */
	public function updateAction(\TYPO3\Tagme\Domain\Model\Tag $tag) {
		$this->tagRepository->update($tag);
		$this->flashMessageContainer->add('Your Tag was updated.');
		$this->redirect('list');
	}

	/**
	 * action delete
	 *
	 * @param \TYPO3\Tagme\Domain\Model\Tag $tag
	 * @return void
	 */
	public function deleteAction(\TYPO3\Tagme\Domain\Model\Tag $tag) {
		$this->tagRepository->remove($tag);
		$this->flashMessageContainer->add('Your Tag was removed.');
		$this->redirect('list');
	}

}
?>