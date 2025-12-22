# Blog PDA - Plugin WordPress

Plugin de Blog personalizado para WordPress com Custom Post Type e atualização automática via GitHub.

## Descrição

Este plugin cria um sistema completo de Blog para WordPress com:

- **Custom Post Type** `blog_post` para gerenciar posts do blog
- **Taxonomias personalizadas**: Categorias e Tags do Blog
- **URLs amigáveis** no formato `/blog/slug-do-post/`
- **Suporte a importação** de posts mantendo slugs originais
- **Atualização automática** via GitHub

## Instalação

1. Faça upload da pasta `blog-pda` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Vá em **Configurações > Links Permanentes** e clique em "Salvar alterações"

## Estrutura de URLs

| Tipo | URL |
|------|-----|
| Arquivo do Blog | `/blog/` |
| Post Individual | `/blog/slug-do-post/` |
| Categoria | `/blog/categoria/nome-categoria/` |
| Tag | `/blog/tag/nome-tag/` |

## Importação de Posts

### Usando WP Import Export (Recomendado)

1. Instale o plugin **WP Import Export** no site de destino
2. Vá em **WP Imp Exp > New Import**
3. Faça upload do arquivo exportado (.csv ou .xml)
4. Na configuração de mapeamento:
   - Selecione **Post Type: blog_post**
   - Mapeie o campo "slug" ou "post_name" para manter a URL original
   - Mapeie todos os campos necessários (título, conteúdo, data, autor, etc.)
5. Execute a importação
6. Vá em **Configurações > Links Permanentes** e salve

### Manter Slugs Originais

O plugin preserva automaticamente os slugs durante a importação. Se o post original tinha a URL:

```
https://www.parquedasaves.com.br/blog/trio-em-foz-do-iguacu/
```

Após importação, a URL será:

```
https://seu-site.com.br/blog/trio-em-foz-do-iguacu/
```

## Atualização Automática via GitHub

O plugin se atualiza automaticamente quando uma nova versão é publicada no GitHub.

### Como publicar uma nova versão:

1. Faça as alterações no código
2. Atualize a versão no arquivo `blog-pda.php`:
   ```php
   * Version: 1.0.1
   ```
   E também a constante:
   ```php
   define('BLOG_PDA_VERSION', '1.0.1');
   ```
3. Commit e push para o repositório
4. Crie uma **Tag** ou **Release** no GitHub com o número da versão (ex: `1.0.1` ou `v1.0.1`)

### Histórico de Versões

O GitHub mantém automaticamente o histórico de todas as versões através das Tags/Releases. Você pode acessar versões anteriores a qualquer momento.

## Estrutura de Arquivos

```
blog-pda/
├── blog-pda.php              # Arquivo principal do plugin
├── includes/
│   └── class-github-updater.php   # Sistema de atualização via GitHub
└── README.md
```

## Requisitos

- WordPress 5.0 ou superior
- PHP 7.4 ou superior

## Recursos do Custom Post Type

- **Editor Gutenberg** habilitado
- **Suporte a**: título, editor, autor, thumbnail, excerpt, comentários, custom fields, revisões
- **REST API** habilitada para uso com Gutenberg e integrações

## Changelog

### 1.0.0
- Versão inicial
- Custom Post Type `blog_post`
- Taxonomias: Categorias e Tags do Blog
- Sistema de atualização via GitHub
- Página de importação com instruções
- Página de configurações

## Autor

Desenvolvido por [Lui](https://github.com/pereira-lui)

## Licença

GPL v2 ou posterior

## Links

- [Repositório GitHub](https://github.com/pereira-lui/blog-pda)
- [Releases/Versões](https://github.com/pereira-lui/blog-pda/releases)
