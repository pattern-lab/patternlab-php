## About Pattern Lab
- [Pattern Lab Website](http://patternlab.io/)
- [About Pattern Lab](http://patternlab.io/about.html)
- [Documentation](http://patternlab.io/docs/index.html)
- [Demo](http://demo.patternlab.io/)

The PHP version of Pattern Lab is, at its core, a static site generator. It combines platform-agnostic assets, like the [Mustache](http://mustache.github.io/)-based patterns and the JavaScript-based viewer, with a PHP-based "builder" that transforms and dynamically builds the Pattern Lab site. By making it a static site generator, Pattern Lab strongly separates patterns, data, and presentation from build logic. 

## Demo

You can play with a demo of the front-end of Pattern Lab at [demo.patternlab.io](http://demo.patternlab.io).

## Getting Started

* [Requirements](http://patternlab.io/docs/requirements.html)
* [Installing the PHP Version of Pattern Lab](http://patternlab.io/docs/installation.html)
* [Upgrading the PHP Version of Pattern Lab](http://patternlab.io/docs/upgrading.html)
* [Generating the Pattern Lab Website for the First Time](http://patternlab.io/docs/first-run.html)
* [Editing the Pattern Lab Website Source Files](http://patternlab.io/docs/editing-source-files.html)
* [Using the Command-line Options](http://patternlab.io/docs/command-line.html)
* [Command Prompt on Windows](http://patternlab.io/docs/command-prompt-windows.html)

## Working with Patterns

Patterns are the core element of Pattern Lab. Understanding how they work is the key to getting the most out of the system. Patterns use [Mustache](http://mustache.github.io/) so please read [Mustache's docs](http://mustache.github.io/mustache.5.html) as well.

* [How Patterns Are Organized](http://patternlab.io/docs/pattern-organization.html)
* [Adding New Patterns](http://patternlab.io/docs/pattern-add-new.html)
* [Reorganizing Patterns](http://patternlab.io/docs/pattern-reorganizing.html)
* [Including One Pattern Within Another via Partials](http://patternlab.io/docs/pattern-including.html)
* [Managing Assets for a Pattern: JavaScript, images, CSS, etc.](http://patternlab.io/docs/pattern-managing-assets.html)
* [Modifying the Pattern Header and Footer](http://patternlab.io/docs/pattern-header-footer.html)
* [Using Pseudo-Patterns](http://patternlab.io/docs/pattern-pseudo-patterns.html)
* [Using Pattern Parameters](http://patternlab.io/docs/pattern-parameters.html)
* [Using Pattern State](http://patternlab.io/docs/pattern-states.html)
* ["Hiding" Patterns in the Navigation](http://patternlab.io/docs/pattern-hiding.html)
* [Adding Annotations](http://patternlab.io/docs/pattern-adding-annotations.html)
* [Viewing Patterns on a Mobile Device](http://patternlab.io/docs/pattern-mobile-view.html)

## Creating & Working With Dynamic Data for a Pattern

The PHP version of Pattern Lab utilizes Mustache as the template language for patterns. In addition to allowing for the [inclusion of one pattern within another](http://patternlab.io/docs/pattern-including.html) it also gives pattern developers the ability to include variables. This means that attributes like image sources can be centralized in one file for easy modification across one or more patterns. The PHP version of Pattern Lab uses a JSON file, `source/_data/data.json`, to centralize many of these attributes.

* [Introduction to JSON & Mustache Variables](http://patternlab.io/docs/data-json-mustache.html)
* [Overriding the Central `data.json` Values with Pattern-specific Values](http://patternlab.io/docs/data-pattern-specific.html)
* [Linking to Patterns with Pattern Lab's Default `link` Variable](http://patternlab.io/docs/data-link-variable.html)
* [Creating Lists with Pattern Lab's Default `listItems` Variable](http://patternlab.io/docs/data-listitems.html)

## Using Pattern Lab's Advanced Features

By default, the Pattern Lab assets can be manually generated and the Pattern Lab site manually refreshed but who wants to waste time doing that? Here are some ways that Pattern Lab can make your development workflow a little smoother:

* [Watching for Changes and Auto-Regenerating Patterns](http://patternlab.io/docs/advanced-auto-regenerate.html)
* [Auto-Reloading the Browser Window When Changes Are Made](http://patternlab.io/docs/advanced-reload-browser.html)
* [Multi-browser & Multi-device Testing with Page Follow](http://patternlab.io/docs/advanced-page-follow.html)
* [Keyboard Shortcuts](http://patternlab.io/docs/advanced-keyboard-shortcuts.html)
* [Special Pattern Lab-specific Query String Variables ](http://patternlab.io/docs/pattern-linking.html)
* [Preventing the Cleaning of public/](http://patternlab.io/docs/advanced-clean-public.html)
* [Generating CSS](http://patternlab.io/docs/advanced-generating-css.html)
* [Modifying the Pattern Lab Nav](http://patternlab.io/docs/advanced-pattern-lab-nav.html)
* [Editing the config.ini Options](http://patternlab.io/docs/advanced-config-options.html)
* [Integration with Compass](http://patternlab.io/docs/advanced-integration-with-compass.html)



-------------------

## Translations of this document
- [Portuguese](https://github.com/pattern-lab/patternlab-php#sobre-pattern-lab)

-------------------


## Sobre Pattern Lab
- [Site Pattern Lab](http://patternlab.io/)
- [Sobre Pattern Lab](http://patternlab.io/about.html)
- [Documentation](http://patternlab.io/docs/index.html)
- [Demo](http://demo.patternlab.io/)


A versão de PHP Pattern Lab é , em sua essência, um gerador de site estático . Ele combina ativos independentes de plataforma , como os [ Mustache] ( http://mustache.github.io/ ) baseados em padrões e o visualizador baseado em JavaScript , com um " construtor " baseado em PHP que transforma e constroi dinamicamente o Pattern Lab Base. Ao torná-lo um gerador de site estático , Pattern Lab separa fortemente padrões , dados e apresentação da lógica de construção.

## Demonstração

Você pode ver a demonstração em [demo.patternlab.io](http://demo.patternlab.io).

## VAMOS COMEÇAR

* [Requisitos](http://patternlab.io/docs/requirements.html)
* [Instalação de versão do PHP de Pattern Lab](http://patternlab.io/docs/installation.html)
* [Atualização da versão do PHP para Pattern Lab](http://patternlab.io/docs/upgrading.html)
* [Gerando Pattern Lab site pela primeira vez](http://patternlab.io/docs/first-run.html)
* [Editando os Arquivos Fontes do Pattern Lab Website ](http://patternlab.io/docs/editing-source-files.html)
* [Usando os Comando de linha](http://patternlab.io/docs/command-line.html)
* [Comandos Prompt no Windows](http://patternlab.io/docs/command-prompt-windows.html)

## Trabalhando com Padrões

Os padrões são o elemento central do Pattern Lab. Entender como elas funcionam é a chave para obter o máximo proveito do sistema. padrões de usar[Mustache](http://mustache.github.io/) apenas leia por favor [Mustache's docs](http://mustache.github.io/mustache.5.html) também.

* [Como Organizar os Padrões](http://patternlab.io/docs/pattern-organization.html)
* [Adicionando Um Novo Padrão](http://patternlab.io/docs/pattern-add-new.html)
* [Reorganizando os Padrões](http://patternlab.io/docs/pattern-reorganizing.html)
* [Incluindo Um Padrão Dentro Outra Via Partials](http://patternlab.io/docs/pattern-including.html)
* [Gerenciando Assets para Pattern: JavaScript, images, CSS, etc.](http://patternlab.io/docs/pattern-managing-assets.html)
* [Modificando Header and Footer do Padrão](http://patternlab.io/docs/pattern-header-footer.html)
* [Usando Pseudo-Padrões](http://patternlab.io/docs/pattern-pseudo-patterns.html)
* [Usando Parametros Padrões](http://patternlab.io/docs/pattern-parameters.html)
* [Usando Estados de Padrões](http://patternlab.io/docs/pattern-states.html)
* ["Ocultando" Padrões na Navegação](http://patternlab.io/docs/pattern-hiding.html)
* [Adicionando Anotações](http://patternlab.io/docs/pattern-adding-annotations.html)
* [Vendo Padrões nos Dispositivos Mobile](http://patternlab.io/docs/pattern-mobile-view.html)

## Criando e Trabalhando com dados dinâmicos para um Padrão

A versão de PHP Pattern Lab utiliza Mustache como o modelo de linguagem de padrões . Além de permitir que para a[iinclusão de um padrão dentro de outro](http://patternlab.io/docs/pattern-including.html) ele também dá aos desenvolvedores a capacidade padrão para incluir variáveis. Isto significa que atributos como fontes de imagem pode ser centralizado em um único arquivo para facilitar a modificação em uma ou mais padrões. A versão de PHP Pattern Lab utiliza arquivos JSON ', `source/_data/data.json`, para centralizar muitos desses atributos.

* [Introdução JSON & Variaves Mustache ](http://patternlab.io/docs/data-json-mustache.html)
* [Substituindo o Central `data.json` com valores específicos do Padrão](http://patternlab.io/docs/data-pattern-specific.html)
* [Vinculando a Patterns com Pattern Lab's Padrão Variaveis `link` ](http://patternlab.io/docs/data-link-variable.html)
* [Criação de listas com Pattern Lab's Padrão  `listItems` ](http://patternlab.io/docs/data-listitems.html)

##Usando Padrão de Laboratório Recursos Avançados


Por padrão , os ativos Padrão Lab podem ser gerados manualmente eo site Lab Padrão atualizado manualmente mas quem quer perder tempo fazendo isso? Aqui estão algumas maneiras que Pattern Lab pode fazer o seu fluxo de trabalho de desenvolvimento um pouco mais suave :

* [Prestando atenção para mudanças e padrões Regenerar ](http://patternlab.io/docs/advanced-auto-regenerate.html)
* [Atualização  automatica da janela do navegador quando são feitas alterações](http://patternlab.io/docs/advanced-reload-browser.html)
* [Multi- navegador & Testing multi- dispositivo com página ](http://patternlab.io/docs/advanced-page-follow.html)
* [Atalhos do teclado](http://patternlab.io/docs/advanced-keyboard-shortcuts.html)
* [Padrão especial específico - Lab Variáveis ​​string query ](http://patternlab.io/docs/pattern-linking.html)
* [Impedindo a limpeza de public/](http://patternlab.io/docs/advanced-clean-public.html)
* [Gerenciando CSS](http://patternlab.io/docs/advanced-generating-css.html)
* [Modificado os Padrões de Navegação](http://patternlab.io/docs/advanced-pattern-lab-nav.html)
* [Editando as opções config.ini ](http://patternlab.io/docs/advanced-config-options.html)
* [Interação com Compass](http://patternlab.io/docs/advanced-integration-with-compass.html)
