<?

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc as Loc;

class CIblockListNews extends CBitrixComponent
{

	protected $cacheKeys = array();
	protected $cacheAddon = array();
	protected $navParams = array();

	protected $returned;


	public function onIncludeComponentLang()
	{
		$this->includeComponentLang(basename(__FILE__));
		Loc::loadMessages(__FILE__);
	}

	 public function onPrepareComponentParams($params)
    {
        $result = array(
            'IBLOCK_TYPE' => trim($params['IBLOCK_TYPE']),
            'IBLOCK_ID' => intval($params['IBLOCK_ID']),
            'SHOW_NAV' => ($params['SHOW_NAV'] == 'Y' ? 'Y' : 'N'),
            'NEWS_COUNT' => intval($params['NEWS_COUNT']),
            'CACHE_TIME' => intval($params['CACHE_TIME']) > 0 ? intval($params['CACHE_TIME']) : 3600,
			'FILTER_NAME' => is_array($params['FILTER_NAME']) && sizeof($params['FILTER_NAME']) ? $params['FILTER_NAME'] : array(),
        );
        return $result;
    }


	protected function readDataFromCache()
	{
		global $USER;
		if ($this->arParams['CACHE_TYPE'] == 'N')
			return false;

		if (is_array($this->cacheAddon))
			$this->cacheAddon[] = $USER->GetUserGroupArray();
		else
			$this->cacheAddon = array($USER->GetUserGroupArray());

		return !($this->startResultCache(false, $this->cacheAddon, md5(serialize($this->arParams))));
	}

protected function putDataToCache()
	{
		if (is_array($this->cacheKeys) && sizeof($this->cacheKeys) > 0)
		{
			$this->SetResultCacheKeys($this->cacheKeys);
		}
	}

	protected function abortDataCache()
	{
		$this->AbortResultCache();
	}

	protected function endCache()
    {
        if ($this->arParams['CACHE_TYPE'] == 'N')
            return false;

        $this->endResultCache();
    }

	protected function checkModules()
	{
		if (!Main\Loader::includeModule('iblock'))
			throw new Main\LoaderException(Loc::getMessage('STANDARD_ELEMENTS_LIST_CLASS_IBLOCK_MODULE_NOT_INSTALLED'));
	}


	protected function checkParams()
	{
		if ($this->arParams['IBLOCK_ID'] <= 0 && strlen($this->arParams['IBLOCK_CODE']) <= 0)
			throw new Main\ArgumentNullException('IBLOCK_ID');
	}



	protected function executeProlog()
	{
		echo "<pre>"; print_r($this); echo "</pre>";
		if($this->arParams["FILTER_NAME"] == '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $this->arParams["FILTER_NAME"]))
		{
			$arrFilter = array();
		}
		else
		{
			$arrFilter = $GLOBALS[$arParams["FILTER_NAME"]];
			if(!is_array($arrFilter))
				$arrFilter = array();
		}


		if ($this->arParams['NEWS_COUNT'] > 0)
		{
			if ($this->arParams['SHOW_NAV'] == 'Y')
			{
				\CPageOption::SetOptionString('main', 'nav_page_in_session', 'N');
				$this->navParams = array(
					'nPageSize' => $this->arParams['NEWS_COUNT']
				);
	    		$arNavigation = \CDBResult::GetNavParams($this->navParams);
				$this->cacheAddon = array($arNavigation);
			}
			else
			{
				$this->navParams = array(	
					'nTopCount' => $this->arParams['NEWS_COUNT']
				);
			}
		}
		else
			$this->navParams = false;
	}

	protected function getIblockId()
    {
        if ($this->arParams['IBLOCK_ID'] <= 0)
        {
            if (class_exists('Settings'))
            {
                $this->arParams['IBLOCK_ID'] = \SiteSettings::getInstance()->getIblockId($this->arParams['IBLOCK_CODE']);
                if ($this->arParams['IBLOCK_ID'] && $this->arParams['CACHE_TAG_OFF'])
                    \CIBlock::disableTagCache($this->arParams['IBLOCK_ID']);
            }
        }


        if ($this->arParams['IBLOCK_ID'] <= 0)
        {
            $sort = array(
                'id' => 'asc'
            );
            $filter = array(
                'TYPE' => $this->arParams['IBLOCK_TYPE'],
                'CODE' => $this->arParams['IBLOCK_CODE']
            );
            $iterator = \CIBlock::GetList($sort, $filter);
            if ($iblock = $iterator->GetNext())
                $this->arParams['IBLOCK_ID'] = $iblock['ID'];
            else
            {
                $this->abortDataCache();
                throw new Main\ArgumentNullException('IBLOCK_ID');
            }
        }
        $this->arResult['IBLOCK_ID'] = $this->arParams['IBLOCK_ID'];
        $this->cacheKeys[] = 'IBLOCK_ID';


		if($arParams["FILTER"] == '' || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["FILTER"]))
{
	$arrFilter = array();
}
else
{
	$arrFilter = $GLOBALS[$arParams["FILTER"]];
	if(!is_array($arrFilter))
		$arrFilter = array();
}
    }

	protected function getResult()
	{
		$filter = array(
			'IBLOCK_TYPE' => $this->arParams['IBLOCK_TYPE'],
			'IBLOCK_ID' => $this->arParams['IBLOCK_ID'],
			'ACTIVE' => 'Y'
		);
		$select = array(
			'ID',
			'NAME',
			'DATE_ACTIVE_FROM',
			'DETAIL_PAGE_URL',
			'PREVIEW_TEXT',
			'PREVIEW_PICTURE',
			'PREVIEW_TEXT_TYPE',
		);
		$iterator = \CIBlockElement::GetList(array("SORT"=> "ASC"), $filter, false, $this->navParams, $select);
		while ($element = $iterator->GetNext())
		{
			$this->arResult['ITEMS'][] = array(
				'ID' => $element['ID'],
				'NAME' => $element['NAME'],
				'DATE' => $element['DATE_ACTIVE_FROM'],
				'URL' => $element['DETAIL_PAGE_URL'],
				"IMG_URL" => $element["PREVIEW_PICTURE"],
				'TEXT' => $element['PREVIEW_TEXT'],
			);

		}
		if ($this->arParams['SHOW_NAV'] == 'Y' && $this->arParams['NEWS_COUNT'] > 0)
		{
			$this->arResult['NAV_STRING'] = $iterator->GetPageNavString('');
		}
	}
	
	/**
	 * выполняет действия после выполения компонента, например установка заголовков из кеша
	 */
	protected function executeEpilog()
	{
		if ($this->arResult['IBLOCK_ID'] && $this->arParams['CACHE_TAG_OFF'])
            \CIBlock::enableTagCache($this->arResult['IBLOCK_ID']);
	}

	/**
	 * выполняет логику работы компонента
	 */
	public function executeComponent()
	{
		global $APPLICATION;
		try
		{
			$this->checkModules();
			$this->checkParams();
			$this->executeProlog();
			if ($this->arParams['AJAX'] == 'Y')
				$APPLICATION->RestartBuffer();
			if (!$this->readDataFromCache())
			{
			    $this->getIblockId();
				$this->getResult();
				$this->putDataToCache();
				$this->includeComponentTemplate();
			}
			$this->executeEpilog();

			if ($this->arParams['AJAX'] == 'Y')
				die();

			return $this->returned;
		}
		catch (Exception $e)
		{
			$this->abortDataCache();
			ShowError($e->getMessage());
		}
	}

}