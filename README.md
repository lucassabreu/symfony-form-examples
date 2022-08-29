Symfony Forms
=============

> ℹ️  Esse repositório foi criado para acompanhar essa apresentação para um time interno

O pacote [`symfony/form`][symfony-form] é um componente do [Symfony][] para trabalhar com formulários HTML,
seja a geração de HTML ou a validação/processamento dos mesmos num servidor.

Nesse repositório vou explorar mais o uso desse componente para validação num servidor funcionando como
[BFF][] para um frontend que não necessariamente é provido pelo servidor processamento esses formulários.

Roteiro:
- Conceitos
- Exemplo básico
- Validação de entradas
- Formulários reutilizáveis
  - Passando parâmetros para tipos
  - Resultado customizado
- Transformações
- Truques para BFF

Conceitos
---------

O Symfony Forms permite que montemos formulários HTML a partir de um objeto PHP, e com isso validarmos se o
mesmo foi enviado, se os dados enviados são válidos e completos, injetar os mesmos diretamente num objeto PHP
ou vetor, e se for usado junto ao pacote de [Twig][twig-bundle] permite gerar o HTML para o formulário da
definição citada.

Dessa forma um formulário do Symfony teria três etapas principais: construção, processamento e renderização.

A construção do formulário pode ser feita direto num Controller/Action ou a partir de [classes
`FormType`][formulario-reutilizavel]. Nas duas situações usamos o [`FormFactory`][form-factory] e precisamos
definir quais os campos que esperamos no formulário, assim como seus tipos e opcionalmente [como
validá-los][validacao-entradas] e [transformá-los][transformacoes].

Por padrão um formulário do Symfony também precisará de nome que será usado para diferenciar múltiplos
formulários numa mesma chamada, assim como controles como CSRF. Esse nome será usado como prefixo para todos
os campos do formulário, se definir a entrada `idade` e o nome do formulário for `pessoa`, então ele espera um
campo `pessoa[idade]` no corpo da requisição.

> 💡 Existem formas de contornar a necessidade do prefixo nas chamadas, visto que tê-lo não é interessantes
> pensando em uma API, como fazer isso será explorado em [Truques para BFF][truques-bff]

O processamento é feito chamando o método `handleRequest` de um formulário, o mesmo irá interpretar a
requisição e verificar se teve o envio do formulário, e feito isso podemos usar os métodos `isSubmitted` e
`isValid` para validar a requisição.

- Se tudo estiver certo no formulário o `isValid` irá retornar `true` e podemos chamar o `getData` do mesmo
  para pegar os dados tratados.
- Se o formulário não tiver sido enviado, o `isSubmitted` irá retornar  `false` e o `getData` não será
  confiável e nenhuma validacão será feita sobre os dados.
- Se o formulário foi enviado, mas houverem problemas com as informações, então o `isValid` irá retornar
  `false` e os erros estarão disponíveis no métodos `getErrors`

Exemplo básico
--------------

Para termos um exemplo, vamos criar um formulário para receber "tarefas", onde uma tarefa terá um nome
(`name`) e um previsão de entrega (`dueDate`), um Controller com esse formulário ficaria:

```php
class Controller
{
    public function __invoke(
        Request $request,
        FormFactoryInterface $formFactory,
        Environment $twig,
    ): Response {
        /** @var FormInterface */
        $form = $formFactory->createBuilder()
            ->add('name', TextType::class)
            ->add('dueDate', DateType::class, ['widget' => 'single_text'])
            ->add('save', SubmitType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse($form->getData());
        }

        return new Response($twig->render('form.html.twig', [
            'form' => $form->createView(),
        ]));
    }
}
```

No código acima estamos usando o `FormFactory` para montar o formulário, usamos o `handleRequest` para
processar a requisição, caso esteja válida estamos retornando os dados válidos como um JSON, se não estiverem,
ou não tiverem sido enviados, então mostramos o formulário em HTML.

O arquivo `form.html.twig` tem o seguinte conteúdo:
```twig
{% extends "base.html.twig" %}
{% block body %} {{ form(form) }} {% endblock %}
```

Se nada for informado o formulário abaixo:

![formulário renderizado](./assets/first-form.png)

Todos os tipos de `input` padrões do HTML5 são suportados, pode velos aqui:
https://symfony.com/doc/current/reference/forms/types.html

Outros tipos mais voltados para problemas e valores usados em aplicações também estão disponíveis, como
UuidType, BirthdayType e EnumType.

Validação de Entradas
---------------------

Até agora podemos definir os campos que existem no formulário e o tipo dos mesmos, mas se chamarmos o endpoint
apenas com o campo `form[name]` sem passar o `form[dueDate]`, ou o contrário, o Symfony intende que a
requisição é válida, e vai preencher o os outros campos com o valor padrão.

Para podermos de fato validar os valores enviados vamos adicionar o pacote [Validator](symfony-validator) do
Symfony (`symfony/validator`). Existe uma variedade de validadores disponíveis, para aplicá-los usaremos o
terceiro parâmetro do `FormType::add`, alterando o exemplo anterior para obrigar a passar os dois campos:

```php
use Symfony\Component\Validator\Constraints\NotBlank;
// ...
$form = $formFactory->createBuilder()
    ->add('name', TextType::class, ['constraints' => [
        new NotBlank(),
    ]])
    ->add('dueDate', DateType::class, ['widget' => 'single_text', 'constraints' => [
        new NotBlank(),
    ]])
    ->add('save', SubmitType::class)
    ->getForm();
// ...
```

Agora os dois campos passam a ser obrigatórios, se não passarmos o `$form->getErrors()` vai ser alimentado, e
se renderizarmos o formulário em HTML as mensagens irão aparecer como abaixo:

![formulário com mensagens de erro](./assets/form-with-error.png)

Podemos fazer outras validações mais interessantes como se o valor informado é um número de cartão de crédito
válido usando o [`Luhn`][val-luhn], ou se é um [`IP`][val-ip] válido.

Existem outras validações mais simples, se um valor está numa faixa, se tem alguns n itens, se é UUID, etc.
Que podem ser usadas para em combinações com tipos de entrada específicas para ter validações melhores.

Voltando para o exemplo original, vamos agora adicionar que o nome da tarefa precisa ter 5 caracteres ou mais,
e que a data prevista é opcional, mas que precisa ser hoje ou no futuro:

```php
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
// ...
$form = $formFactory->createBuilder()
    ->add('name', TextType::class, ['constraints' => [
        new NotBlank(),
        new Length(min: 5),
    ]])
    ->add('dueDate', DateType::class, ['widget' => 'single_text', 'constraints' => [
        new GreaterThanOrEqual(new \DateTime('today'), message: 'Due Date can not be in the past'),
    ]])
    ->add('save', SubmitType::class)
    ->getForm();
// ...
```

Se tentarmos informar errado iremos receber:

![form with new errors](./assets/form-with-new-errors.png)

Infelizmente as validações acima vão ser aplicadas apenas no processamento do formulário no backend, ou seja,
a versão renderizada do formulário não irá adicionar os atributos equivalentes para que o navegador trate,
para isso temos de adicioná-los "manualmente" usando a opção `attr` ao adicioná-los, para que o `name`
valide que tem mais de 5 caracteres ficaria:
```php
    ->add('name', TextType::class, [
        'attr' => ['minlength' => 5],
        'constraints' => [new NotBlank(), new Length(min: 5)],
    ])
```

Formulários reutilizáveis
-------------------------

Algo comum em quase todo sistema será que alguns endpoints terão entradas similares ou irão ter campos que tem
o mesmo proposito ou validação entre eles. Para tratar essas situações podemos mover a definição de um
formulário para uma classe dedicada que irá montar os formulários/adicionar validações e os Controllers e
outros formulários podem simplesmente referenciá-las.

Vamos dizer que queremos aproveitar a definição do formulário de tarefas que temos hoje, para isso vamos criar
uma nova classe que estende [`AbstractType`][abst-type], essa classe será como abaixo:

```php
<?php

namespace App\Controller\Reusing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\GreaterThanOrEqual;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class TaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['constraints' => [
                new NotBlank(),
                new Length(min: 5),
            ]])
            ->add('dueDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'constraints' => [new GreaterThanOrEqual(
                    new \DateTime('today'),
                    message: 'Due Date can not be in the past'
                )],
            ])
            ->add('save', SubmitType::class);
    }
}
```

E agora podemos criar um `UpdateTaskType` que "estende" `TaskType`, mas pede o `ID` da tarefa para
atualizar. Esse novo `Type` fica assim:

```php
<?php

namespace App\Controller\Reusing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UpdateTaskType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', IntegerType::class, [
                'priority' => 1, // just to put it into the top
                'constraints' => [new NotBlank()],
            ]);
    }

    public function getParent(): string
    {
        return TaskType::class;
    }
}
```

No Controller vamos fazer uma alteração para usar o `UpdateTaskType` no lugar de montar no método, no final
tudo fica igual exceto a montagem:
```php
class Controller implements ControllerInterface
{
    public function __invoke(/** ... */): Response {
        /** @var FormInterface */
        $form = $formFactory->createNamedBuilder('form', UpdateTaskType::class)
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            return new JsonResponse($form->getData());
        }
        // ...
```

O `createNamedBuilder` funcional igual ao `createBuilder`, exceto que ele permite dar um nome para o
formulário no lugar de deixar o nome definido no `Type` ou o autogerado.

O resultado disso é como abaixo:

![extended form](./assets/extend-form.png)

Vamos dizer que agora queremos poder vincular qual é o usuário responsável pela tarefa assim como quem são as
pessoas interessadas na tarefa. Esses dois são informados passando o nome de usuário, e vamos precisar validar
que é um usuário válido, a regra será que aceita apenas letras minusculas e números.

Para isso vamos criar um `Type` chamado `UsernameType` para fazer essa validação:
```php
<?php

namespace App\Controller\Reusing;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Regex;

class UsernameType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('constraints', [
            new Length(min: 5),
            new Regex(
                '/^[a-z0-9]+$/',
                message: 'Username must constain only letters and numbers'
            ),
        ]);
    }
}
```

Feito isso podemos adicionar no `TaskType` da seguinte forma:
```php
            ->add('assignee', UsernameType::class)
            ->add('stackholders', CollectionType::class, [
                'entry_type' => UsernameType::class,
                'allow_add' => true,
                'allow_delete' => true,
            ])
```

Agora se renderizamos esse formulário e tentar informar valores incorretos teremos a seguinte imagem:

![form with assignee and stackholders](./assets/form-with-assignee.png)

Como nesse formulário tivemos um campo que se repete usamos o [`CollectionType`][coll-type] que é a forma como
o Symfony permite adicionar coleções de objetos, aqui estamos usando apenas um valor, mas poderia ser um
`FormType` mais complexo como uma versão do `TaskType`.

### Resultados customizados

Podendo montar formulários, criar tipos customizados e reutilizar esses formulários podemos explorar uma
funcionalidade muito importante do Symfony Forms, que é poder informar uma classe de dados (`data_class`) para
ele alimentar no lugar de retornar um vetor.

Para uma classe poder ser usada como `data_class` duas coisas são necessárias:
- a classe não pode ter argumentos obrigatórios no construtor
- todos as propriedades que forem alimentados pelo `FormType` precisam ser públicas, ou terem um "setter"
  público.

Quase qualquer classe consegue atender esses requisitos, mas a minha recomendação seria que sejam usados
[DTOs][dto] no lugar de entidades, primeiro porque esse tipo de objeto é pensando para isso, e evita que o
estado das entidades fique inválido, mesmo que temporariamente, enquanto o Symfony Forms está alimentando e
validando os campos.

Um DTO que poderia ser usado para o `UpdateTaskType` que temos agora seria assim:

```php
<?php

namespace App\Controller\DataClass;

class TaskDTO
{
    private int $id;
    private string $name;
    private ?\DateTime $dueDate = null;
    private string $assignee;
    /** @var string[] */
    private array $stackholders = [];

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDueDate(?\DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function setAssignee(string $assignee): void
    {
        $this->assignee = $assignee;
    }

    /** @param string[] $stackholders */
    public function setStackholders(array $stackholders): void
    {
        $this->stackholders = $stackholders;
    }

    // getters
}
```

Para o Symfony Forms usar esse DTO basta adicionar o método `configureOptions` e configurar o campo
`data_class`, como abaixo:

```php
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', TaskDTO::class);
    }
```

[val-host]: https://symfony.com/doc/current/reference/constraints/Hostname.html
[val-luhn]: https://symfony.com/doc/current/reference/constraints/Luhn.html
[val-ip]: https://symfony.com/doc/current/reference/constraints/Ip.html
[symfony-form]: https://symfony.com/doc/current/forms.html
[Symfony]: https://symfony.com/
[BFF]: https://samnewman.io/patterns/architectural/bff/
[form-factory]: https://github.com/symfony/form/blob/0a1a3ea071a216e2902cebe0b47750ca51f12f27/FormFactory.php#L17
[abst-type]: https://github.com/symfony/symfony/blob/8084eb83a44a3639a95f0860456d737b7f2751dd/src/Symfony/Component/Form/AbstractType.php#L21
[formulario-reutilizavel]: #formulários-reutilizáveis
[truques-bff]: #
[twig-bundle]: https://symfony.com/components/Twig%20Bundle
[validacao-entradas]: #validação-de-entradas
[transformacoes]: #
[symfony-validator]: https://symfony.com/doc/current/validation.html
[coll-type]: https://symfony.com/doc/current/reference/forms/types/collection.html

<!-- vim: textwidth=110 colorcolumn=111
