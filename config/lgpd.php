<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Controlador de dados (LGPD)
    |--------------------------------------------------------------------------
    |
    | Nome da instituição responsável pelo tratamento dos dados pessoais
    | coletados neste portal, exibido nos avisos e na política de privacidade.
    |
    */

    'data_controller' => env('LGPD_DATA_CONTROLLER', 'Universidade do Estado do Amazonas (UEA) — ProEnSP'),

    /*
    |--------------------------------------------------------------------------
    | Canal de contato para titulares de dados
    |--------------------------------------------------------------------------
    */

    'contact_email' => env('LGPD_CONTACT_EMAIL', env('MAIL_FROM_ADDRESS', 'privacidade@uea.edu.br')),

];
