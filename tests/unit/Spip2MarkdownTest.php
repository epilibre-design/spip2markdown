<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/spip2markdown_options.php';

final class Spip2MarkdownTest extends TestCase
{
    // --- Normalisation des retours chariot ---

    public function testNormaliserCRLF(): void
    {
        $this->assertEquals("a\nb", spip2markdown_normaliser_retours_chariot("a\r\nb"));
    }

    public function testNormaliserLFCR(): void
    {
        $this->assertEquals("a\nb", spip2markdown_normaliser_retours_chariot("a\n\rb"));
    }

    // --- Gras ---

    public function testGrasSeul(): void
    {
        $this->assertEquals('**gras**', spip2markdown_gras('{{gras}}'));
    }

    public function testGrasDansPhrase(): void
    {
        $this->assertEquals('texte **gras** suite', spip2markdown_gras('texte {{gras}} suite'));
    }

    public function testGrasDouble(): void
    {
        $this->assertEquals('**a** et **b**', spip2markdown_gras('{{a}} et {{b}}'));
    }

    // --- Italiques ---

    public function testItaliqueSeul(): void
    {
        $this->assertEquals('*italique*', spip2markdown_italiques('{italique}'));
    }

    public function testItaliqueDansPhrase(): void
    {
        $this->assertEquals('texte *italique* suite', spip2markdown_italiques('texte {italique} suite'));
    }

    // --- Intertitres ---

    public function testIntertitre(): void
    {
        $this->assertEquals("\n## Titre\n", spip2markdown_intertitres('{{{Titre}}}'));
    }

    public function testIntertitreAvecEspaces(): void
    {
        $this->assertEquals("\n## Mon titre\n", spip2markdown_intertitres('{{{Mon titre}}}'));
    }

    // --- Citations ---

    public function testCitation(): void
    {
        $this->assertEquals("\n> texte", spip2markdown_citations('<quote>texte</quote>'));
    }

    public function testCitationMultiligne(): void
    {
        $result = spip2markdown_citations("<quote>ligne1\nligne2</quote>");
        $this->assertEquals("\n> ligne1\n> ligne2", $result);
    }

    // --- Listes non ordonnées ---

    public function testListeSimpleTiret(): void
    {
        $this->assertEquals("\n- item", spip2markdown_listes_non_ordonnees('- item'));
    }

    public function testListeSimpleEtoile(): void
    {
        $this->assertEquals("\n- item", spip2markdown_listes_non_ordonnees('-* item'));
    }

    public function testListeNiveau2(): void
    {
        $this->assertEquals('    - sous-item', spip2markdown_listes_non_ordonnees('-** sous-item'));
    }

    public function testListeNiveau3(): void
    {
        $this->assertEquals('        - niveau3', spip2markdown_listes_non_ordonnees('-*** niveau3'));
    }

    public function testListeNiveau4(): void
    {
        $this->assertEquals('            - niveau4', spip2markdown_listes_non_ordonnees('-**** niveau4'));
    }

    // --- Listes ordonnées ---

    public function testListeOrdonneeNiveau1(): void
    {
        $this->assertEquals("\n1. item", spip2markdown_listes_ordonnees('-# item'));
    }

    public function testListeOrdonneeNiveau1ApresSaut(): void
    {
        // Régression ${1} : sans le fix, groupe 1 (\n) était perdu → \n1. au lieu de \n\n1.
        $this->assertEquals("texte\n\n1. item", spip2markdown_listes_ordonnees("texte\n-# item"));
    }

    public function testListeOrdonneeNiveau2(): void
    {
        $this->assertEquals('    1. sous-item', spip2markdown_listes_ordonnees('-## sous-item'));
    }

    public function testListeOrdonneeNiveau3(): void
    {
        $this->assertEquals('        1. niveau3', spip2markdown_listes_ordonnees('-### niveau3'));
    }

    // --- Notes de bas de page ---

    public function testNoteSimple(): void
    {
        $result = spip2markdown_notes('texte[[note]]', '');
        $this->assertStringContainsString('[^1]', $result);
        $this->assertStringContainsString('[^1]: note', $result);
        $this->assertLessThan(strpos($result, '[^1]: note'), strpos($result, '[^1]'));
    }

    public function testNoteAvecContexte(): void
    {
        $result = spip2markdown_notes('texte[[note]]', 'ctx');
        $this->assertStringContainsString('[^ctx1]', $result);
        $this->assertStringContainsString('[^ctx1]: note', $result);
    }

    public function testDeuxNotes(): void
    {
        $result = spip2markdown_notes('a[[note1]]b[[note2]]', '');
        $this->assertStringContainsString('[^1]', $result);
        $this->assertStringContainsString('[^2]', $result);
        $this->assertStringContainsString('[^1]: note1', $result);
        $this->assertStringContainsString('[^2]: note2', $result);
    }

    // --- Liens externes (sans SQL) ---

    public function testLienAvecLibelle(): void
    {
        $this->assertEquals(
            '[libellé](https://example.com)',
            spip2markdown_liens('[libellé->https://example.com]')
        );
    }

    public function testLienSansLibelle(): void
    {
        $this->assertEquals(
            '<https://example.com>',
            spip2markdown_liens('[->https://example.com]')
        );
    }

    public function testDeuxLiens(): void
    {
        $this->assertEquals(
            '[a](https://a.com) et [b](https://b.com)',
            spip2markdown_liens('[a->https://a.com] et [b->https://b.com]')
        );
    }

    // --- Code inline ---

    public function testCodeInline(): void
    {
        [$text, $code] = spip2markdown_extraire_code('avant <code>echo "hi";</code> après');
        $this->assertEquals('avant `echo "hi";` après', spip2markdown_reinserer_code($text, $code));
    }

    // --- Code en bloc ---

    public function testCodeBloc(): void
    {
        $input = "avant\n<code>\necho 'hi';\n</code>\naprès";
        [$text, $code] = spip2markdown_extraire_code($input);
        $this->assertEquals("avant\n```\necho 'hi';\n```\naprès", spip2markdown_reinserer_code($text, $code));
    }

    public function testCodeBlocAvecLangue(): void
    {
        $input = "avant\n<code class=\"php\">\n\$x = 1;\n</code>\naprès";
        [$text, $code] = spip2markdown_extraire_code($input);
        $this->assertEquals("avant\n```php\n\$x = 1;\n```\naprès", spip2markdown_reinserer_code($text, $code));
    }

    // --- YouTube ---

    public function testYoutube(): void
    {
        $this->assertEquals(
            '{% youtube abc123 %}',
            spip2markdown_youtube('<iframe src="https://www.youtube.com/embed/abc123"></iframe>')
        );
    }

    public function testYoutubeAvecParametres(): void
    {
        $this->assertEquals(
            '{% youtube abc123 %}',
            spip2markdown_youtube('<iframe src="https://www.youtube.com/embed/abc123?rel=0"></iframe>')
        );
    }

    // --- Nettoyer ---

    public function testNettoyerRetourChariotDebutFin(): void
    {
        $this->assertEquals('texte', spip2markdown_nettoyer("\n\ntexte\n\n"));
    }

    public function testNettoyerCenter(): void
    {
        $this->assertEquals('texte', spip2markdown_nettoyer('<CENTER>texte</CENTER>'));
    }

    public function testNettoyerCenterMinuscule(): void
    {
        $this->assertEquals('texte', spip2markdown_nettoyer('<center>texte</center>'));
    }
}
