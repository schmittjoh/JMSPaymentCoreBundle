<?php

declare(strict_types=1);

use PHP_CodeSniffer\Standards\Generic\Sniffs\CodeAnalysis\UselessOverridingMethodSniff;
use PHP_CodeSniffer\Standards\Generic\Sniffs\Functions\FunctionCallArgumentSpacingSniff;
use PHP_CodeSniffer\Standards\PEAR\Sniffs\WhiteSpace\ObjectOperatorIndentSniff;
use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use PhpCsFixer\Fixer\ArrayNotation\NoMultilineWhitespaceAroundDoubleArrowFixer;
use PhpCsFixer\Fixer\CastNotation\CastSpacesFixer;
use PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer;
use PhpCsFixer\Fixer\ClassNotation\NoBlankLinesAfterClassOpeningFixer;
use PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer;
use PhpCsFixer\Fixer\ClassNotation\VisibilityRequiredFixer;
use PhpCsFixer\Fixer\ControlStructure\TrailingCommaInMultilineFixer;
use PhpCsFixer\Fixer\ControlStructure\YodaStyleFixer;
use PhpCsFixer\Fixer\FunctionNotation\FunctionTypehintSpaceFixer;
use PhpCsFixer\Fixer\FunctionNotation\UseArrowFunctionsFixer;
use PhpCsFixer\Fixer\FunctionNotation\VoidReturnFixer;
use PhpCsFixer\Fixer\Import\NoUnusedImportsFixer;
use PhpCsFixer\Fixer\Import\SingleLineAfterImportsFixer;
use PhpCsFixer\Fixer\NamespaceNotation\SingleBlankLineBeforeNamespaceFixer;
use PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer;
use PhpCsFixer\Fixer\Phpdoc\AlignMultilineCommentFixer;
use PhpCsFixer\Fixer\Phpdoc\PhpdocIndentFixer;
use PhpCsFixer\Fixer\ReturnNotation\ReturnAssignmentFixer;
use PhpCsFixer\Fixer\StringNotation\SingleQuoteFixer;
use PhpCsFixer\Fixer\Whitespace\BlankLineBeforeStatementFixer;
use PhpCsFixer\Fixer\Whitespace\MethodChainingIndentationFixer;
use PhpCsFixer\Fixer\Whitespace\NoExtraBlankLinesFixer;
use Symplify\CodingStandard\Fixer\LineLength\LineLengthFixer;
use Symplify\EasyCodingStandard\Config\ECSConfig;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ECSConfig $containerConfigurator): void {
    $containerConfigurator->import(SetList::PSR_12);
    $containerConfigurator->import(SetList::CLEAN_CODE);
    $containerConfigurator->import(SetList::STRICT);
    $containerConfigurator->import(SetList::ARRAY);
    $containerConfigurator->import(SetList::DOCTRINE_ANNOTATIONS);

    $containerConfigurator->cacheDirectory('.ecs_cache');
    $containerConfigurator->indentation(str_repeat(' ', 4));
    $containerConfigurator->lineEnding("\n");
    $containerConfigurator->parallel();
    $containerConfigurator->paths([__DIR__]);

    $services = $containerConfigurator->services();
    $services
        ->set(UselessOverridingMethodSniff::class)
        ->set(ReturnAssignmentFixer::class)
        ->set(ObjectOperatorIndentSniff::class)
        ->set(FunctionCallArgumentSpacingSniff::class)
        ->set(AlignMultilineCommentFixer::class)
        ->set(CastSpacesFixer::class)
        ->set(FunctionTypehintSpaceFixer::class)
        ->set(MethodChainingIndentationFixer::class)
        ->set(PhpdocIndentFixer::class)
        ->set(ArraySyntaxFixer::class)
        ->call('configure', [[
            'syntax' => 'short',
        ]])
        ->set(LineLengthFixer::class)
        ->call('configure', [[
            LineLengthFixer::BREAK_LONG_LINES => true,
            LineLengthFixer::INLINE_SHORT_LINES => true,
            LineLengthFixer::LINE_LENGTH => 120,
        ]])
        ->set(NoBlankLinesAfterClassOpeningFixer::class)
        ->set(TrailingCommaInMultilineFixer::class)
        ->set(UseArrowFunctionsFixer::class)
        ->set(VisibilityRequiredFixer::class)
        ->set(VoidReturnFixer::class)
        ->set(SingleBlankLineBeforeNamespaceFixer::class)
        ->set(NoMultilineWhitespaceAroundDoubleArrowFixer::class)
        ->set(SingleTraitInsertPerStatementFixer::class)
        ->set(SingleQuoteFixer::class)
        ->set(ClassAttributesSeparationFixer::class)
        ->call('configure', [[
            'elements' => [
                'const' => 'none',
                'property' => 'one',
            ],
        ]])
        ->set(NoUnusedImportsFixer::class)
        ->set(NoMultilineWhitespaceAroundDoubleArrowFixer::class)
        ->set(BlankLineBeforeStatementFixer::class)
        ->set(SingleLineAfterImportsFixer::class)
        ->set(NoExtraBlankLinesFixer::class)
        ->set(BinaryOperatorSpacesFixer::class)
        ->call('configure', [[
            'default' => 'single_space',
        ]])
        ->set(YodaStyleFixer::class)
    ;
};
