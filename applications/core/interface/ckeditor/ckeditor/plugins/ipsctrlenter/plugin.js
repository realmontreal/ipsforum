﻿CKEDITOR.plugins.add("ipsctrlenter",{init:function(c){c.setKeystroke(CKEDITOR.CTRL+13,"ipsCtrlEnter");c.addCommand("ipsCtrlEnter",{exec:function(b){var a=$("."+b.id).closest("form").find('[data-role\x3d"primarySubmit"]');a.length||(a=$("."+b.id).closest("form").find('button[type\x3d"submit"]'));a.length?a.click():$("."+b.id).closest("form").submit()}})}});