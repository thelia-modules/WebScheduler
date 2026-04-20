<?php

declare(strict_types=1);

namespace WebScheduler\Form;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;
use WebScheduler\Enum\ExecutionStrategyEnum;
use WebScheduler\WebScheduler;

class TaskForm extends BaseForm
{
    protected function buildForm(): void
    {
        $translator = Translator::getInstance();
        $domain = WebScheduler::DOMAIN_NAME;

        $strategyChoices = [];

        foreach (ExecutionStrategyEnum::cases() as $case) {
            $strategyChoices[$translator->trans($case->label(), [], $domain)] = $case->value;
        }

        $this->formBuilder
            ->add('title', TextType::class, [
                'label' => $translator->trans('Title', [], $domain),
                'constraints' => [new NotBlank()],
            ])
            ->add('command_name', TextType::class, [
                'label' => $translator->trans('Command', [], $domain),
                'constraints' => [new NotBlank()],
            ])
            ->add('command_arguments', TextType::class, [
                'required' => false,
                'label' => $translator->trans('Arguments', [], $domain),
            ])
            ->add('strategy', ChoiceType::class, [
                'label' => $translator->trans('Strategy', [], $domain),
                'choices' => $strategyChoices,
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => $translator->trans('Enabled', [], $domain),
            ])
            ->add('min_interval_seconds', IntegerType::class, [
                'label' => $translator->trans('Minimum interval (seconds)', [], $domain),
                'constraints' => [new GreaterThanOrEqual(0)],
            ])
            ->add('max_runtime_seconds', IntegerType::class, [
                'label' => $translator->trans('Maximum runtime (seconds)', [], $domain),
                'constraints' => [new GreaterThanOrEqual(0)],
            ])
            ->add('ip_allowlist', TextareaType::class, [
                'required' => false,
                'label' => $translator->trans('IP allowlist', [], $domain),
            ]);
    }
}
