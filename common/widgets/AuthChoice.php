<?php

namespace common\widgets;

use yii\authclient\widgets\AuthChoice as AuthWidget;
use yii\helpers\Html;

class AuthChoice extends AuthWidget
{
	/**
	 * Outputs client auth link.
	 * @param ClientInterface $client external auth client instance.
	 * @param string $text link text, if not set - default value will be generated.
	 * @param array $htmlOptions link HTML options.
	 */
	public function clientLink($client, $text = null, array $htmlOptions = [])
	{
		if ($text === null) {
			$text = Html::tag('span', '', ['class' => 'auth-icon ' . $client->getName()]);
			$text .= Html::tag('span', $client->getTitle(), ['class' => 'auth-title']);
		}
		if (!array_key_exists('class', $htmlOptions)) {
			$htmlOptions['class'] = 'auth-link ' . $client->getName();
		}
		if ($this->popupMode) {
			$viewOptions = $client->getViewOptions();
			if (isset($viewOptions['popupWidth'])) {
				$htmlOptions['data-popup-width'] = $viewOptions['popupWidth'];
			}
			if (isset($viewOptions['popupHeight'])) {
				$htmlOptions['data-popup-height'] = $viewOptions['popupHeight'];
			}
		}
		echo Html::a($text, $this->createClientUrl($client), $htmlOptions);
	}
	
	/**
	 * Renders the main content, which includes all external services links.
	 */
	protected function renderMainContent()
	{
		echo Html::beginTag('div', ['class' => 'auth-clients clear']);
		foreach ($this->getClients() as $externalService) {
			echo Html::beginTag('div', ['class' => 'auth-client']);
			$this->clientLink($externalService);
			echo Html::endTag('div');
		}
		echo Html::endTag('div');
	}
}