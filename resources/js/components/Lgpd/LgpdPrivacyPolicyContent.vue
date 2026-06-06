<script setup lang="ts">
defineProps<{
    dataController: string;
    contactEmail: string;
}>();
</script>

<template>
    <article class="space-y-6 text-sm leading-relaxed">
        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                1. Controlador de dados
            </h2>
            <p class="text-muted-foreground">
                O controlador dos dados pessoais tratados neste portal é
                <strong class="text-foreground">{{ dataController }}</strong
                >, responsável pelas decisões referentes ao tratamento dos dados
                dos candidatos em processos seletivos.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                2. Dados coletados
            </h2>
            <p class="text-muted-foreground">
                Podemos tratar, entre outros, os seguintes dados:
            </p>
            <ul class="list-disc space-y-1 pl-5 text-muted-foreground">
                <li>
                    Identificação: nome completo, CPF, RG (identidade), órgão
                    emissor, data de emissão;
                </li>
                <li>
                    Dados civis: data de nascimento, naturalidade,
                    nacionalidade, sexo;
                </li>
                <li>Contato: e-mail, telefone;</li>
                <li>
                    Endereço: CEP, logradouro, número, bairro, cidade, UF e
                    país;
                </li>
                <li>Imagem: foto de perfil para identificação na inscrição;</li>
                <li>
                    Dados de acesso: credenciais de login e registros técnicos
                    de uso do sistema.
                </li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                3. Dados pessoais sensíveis
            </h2>
            <p class="text-muted-foreground">
                Nos termos do art. 5º, II, da LGPD, alguns dados informados
                podem ser classificados como
                <strong class="text-foreground"
                    >dados pessoais sensíveis</strong
                >
                (por exemplo, quando relacionados à origem racial ou étnica,
                convicção religiosa, opinião política, filiação sindical, dados
                referentes à saúde ou à vida sexual, dados genéticos ou
                biométricos). O tratamento ocorre somente quando necessário à
                finalidade do processo seletivo e com base legal adequada,
                incluindo consentimento do titular quando aplicável.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                4. Finalidade e bases legais
            </h2>
            <p class="text-muted-foreground">Os dados são tratados para:</p>
            <ul class="list-disc space-y-1 pl-5 text-muted-foreground">
                <li>Cadastro e autenticação de candidatos no portal;</li>
                <li>
                    Identificação e acompanhamento de inscrições em processos
                    seletivos;
                </li>
                <li>
                    Comunicação sobre etapas, prazos e resultados do processo;
                </li>
                <li>
                    Cumprimento de obrigações legais e regulatórias aplicáveis à
                    instituição.
                </li>
            </ul>
            <p class="text-muted-foreground">
                As bases legais incluem, conforme o caso, execução de políticas
                públicas/educacionais, cumprimento de obrigação legal, legítimo
                interesse e consentimento do titular (art. 7º e art. 11 da
                LGPD).
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                5. Compartilhamento
            </h2>
            <p class="text-muted-foreground">
                Os dados podem ser compartilhados com servidores, comissões de
                seleção e prestadores de serviço que atuem em nome do
                controlador, sempre com medidas de confidencialidade e
                segurança. Não comercializamos dados pessoais.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                6. Segurança
            </h2>
            <p class="text-muted-foreground">
                Adotamos medidas técnicas e administrativas para proteger os
                dados contra acessos não autorizados, destruição, perda,
                alteração ou divulgação indevida, incluindo comunicação
                criptografada (HTTPS), controle de acesso por perfil e
                armazenamento seguro.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">7. Retenção</h2>
            <p class="text-muted-foreground">
                Os dados são mantidos pelo tempo necessário à finalidade do
                processo seletivo e aos prazos legais de guarda de documentos,
                após os quais poderão ser eliminados ou anonimizados.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                8. Direitos do titular
            </h2>
            <p class="text-muted-foreground">
                Você pode solicitar, nos termos da LGPD:
            </p>
            <ul class="list-disc space-y-1 pl-5 text-muted-foreground">
                <li>
                    Confirmação da existência de tratamento e acesso aos dados;
                </li>
                <li>
                    Correção de dados incompletos, inexatos ou desatualizados;
                </li>
                <li>
                    Anonimização, bloqueio ou eliminação de dados
                    desnecessários;
                </li>
                <li>Portabilidade dos dados a outro fornecedor de serviço;</li>
                <li>
                    Informação sobre compartilhamentos e revogação do
                    consentimento.
                </li>
            </ul>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">9. Contato</h2>
            <p class="text-muted-foreground">
                Para exercer seus direitos ou esclarecer dúvidas sobre o
                tratamento de dados, entre em contato pelo e-mail
                <a
                    :href="`mailto:${contactEmail}`"
                    class="font-semibold text-primary underline underline-offset-2"
                >
                    {{ contactEmail }} </a
                >.
            </p>
        </section>

        <section class="space-y-2">
            <h2 class="text-base font-semibold text-foreground">
                10. Alterações
            </h2>
            <p class="text-muted-foreground">
                Esta política pode ser atualizada periodicamente. Recomendamos a
                leitura desta página sempre que utilizar o portal.
            </p>
        </section>

        <p class="border-t border-border pt-4 text-xs text-muted-foreground">
            Última atualização: maio de 2026.
        </p>
    </article>
</template>
