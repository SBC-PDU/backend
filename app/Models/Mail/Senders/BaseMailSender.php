<?php

declare(strict_types = 1);

/**
 * Copyright 2022-2023 Roman Ondráček
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Models\Mail\Senders;

use App\Models\Database\Entities\User;
use Contributte\Translation\Translator;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Bridges\ApplicationLatte\TemplateFactory;
use Nette\Mail\Mailer;
use Nette\Mail\Message;

/**
 * Base e-mail sender
 */
abstract class BaseMailSender {

	/**
	 * Constructor
	 * @param Mailer $mailer Mailer
	 * @param TemplateFactory $templateFactory Template factory
	 * @param Translator $translator Translator
	 */
	public function __construct(
		private readonly Mailer $mailer,
		protected readonly TemplateFactory $templateFactory,
		protected readonly Translator $translator,
	) {
	}

	/**
	 * Creates the Latte template for the e-mail
	 * @return Template Latte template for the e-mail
	 */
	protected function createTemplate(): Template {
		$template = $this->templateFactory->createTemplate();
		$latte = $template->getLatte();
		$latte->addProvider('translator', $this->translator);
		return $template;
	}

	/**
	 * Creates a new e-mail message from the Latte template
	 * @param string $fileName Template filename
	 * @param array<string, mixed> $params Template params
	 * @param User|null $user Recipient
	 */
	protected function sendMessage(string $fileName, array $params = [], ?User $user = null): void {
		$defaultParams = [
			'user' => $user,
		];
		if ($user !== null) {
			$this->translator->setLocale($user->language->value);
		}
		$html = $this->renderTemplate($fileName, array_merge($defaultParams, $params));
		$mail = new Message();
		$mail->setFrom('sbc_pdu@romanondracek.cz', $this->translator->translate('mail.title', $user->language->value));
		if ($user !== null) {
			$mail->addTo($user->getEmail(), $user->name);
		}
		$mail->setHtmlBody($html, __DIR__ . '/templates/');
		$this->mailer->send($mail);
	}

	/**
	 * Renders the Latte template for the e-mail
	 * @param string $fileName Template file name
	 * @param array<string, mixed> $params Template parameters
	 * @return string Rendered template
	 */
	protected function renderTemplate(string $fileName, array $params): string {
		return $this->createTemplate()
			->renderToString(__DIR__ . '/templates/' . $fileName, $params);
	}

}
