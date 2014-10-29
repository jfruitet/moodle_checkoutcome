/**
 * @package    mod
 * @subpackage checkoutcome
 * @copyright  2012 Olivier Le Borgne <olivier.leborgne@univ-nantes.fr>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.mod_checkoutcome = { 
    init : function (Y, url, sesskey, cmid, checkoutcomeid, periodid, studentid, groupid, imgediturl, imgaddurl, imgdelurl, displaycolor, test) {
		"use strict";
		Y.use('yui2-dom', 'yui2-event', 'yui2-connection', 'yui2-dragdrop', 'yui2-container', 'yui2-animation', 'yui2-yahoo', 'yui2-element', 'yui2-button', 'yui2-slider', 'yui2-draganddrop', 'yui2-colorpicker', function (Y) {
            var chk, YAHOO = Y.YUI2;
            chk = {
				serverurl: null,
				sesskey: null,
				cmid: null,
				checkoutcomeid: null,
				updategradelist: null,
				updategradetimeout: null,
				updatecountgoallist: null,
				updatecountgoaltimeout: null,
				updatecountlist: null,
				updatecounttimeout: null,
				studentid: null,
				displaycolor: null,
				groupid: null,
				periodid: null,
				clickcountgoaltimeout: null,
			
				init : function (Y, url, sesskey, cmid, checkoutcomeid, periodid, studentid, groupid, imgediturl, imgaddurl, imgdelurl, displaycolor, test)
				{
					if(test=='init_view_items')
					{
						chk.init_view_items(Y, url, sesskey, cmid, checkoutcomeid, periodid);
					}
					if(test=='init_view_items_teacher')
					{
						chk.init_view_items_teacher(Y, url, sesskey, cmid, checkoutcomeid, studentid, groupid, periodid);
					}
					if(test=='init_edit_items')
					{
						chk.init_edit_items(Y, url, sesskey, cmid, checkoutcomeid, imgediturl, imgaddurl, imgdelurl);
					}
					if(test=='init_view_display')
					{
						chk.init_view_display(Y, url, sesskey, cmid, checkoutcomeid, displaycolor);
					}
					if(test=='init_defaults')
					{
						chk.init_defaults(Y,$defaults);
					}
				},
				init_defaults : function(Y,$defaults) {
					// just to load the module			
				},
				
				init_view_items : function(Y, url, sesskey, cmid, checkoutcomeid, periodid) {			
					
					this.serverurl = url;
					this.sesskey = sesskey;
					this.cmid = cmid;
					this.checkoutcomeid = checkoutcomeid;
					this.periodid = periodid;
					
					var YE = YAHOO.util.Event;
					var YD = YAHOO.util.Dom;
					
					// Add event to store student self rating
					this.updategradelist = new Array();
					var selects = document.getElementsByTagName('select');
					for (var i=0; i<selects.length; i++) {
						YE.addListener(selects[i], 'change', function (e) {
							chk.select_change(this, e);
						});
					}
					
					
					// Add event to the student's add_comment button
					var add_comments = document.getElementsByName('add_comment');
					for (var i=0; i<add_comments.length; i++) {
						YE.addListener(add_comments[i], 'click', function (e) {
							chk.add_comment_click(this, e);
						});
					}
					
					// Add event to the student's edit comment button
					var edit_comments = document.getElementsByName('edit_comment');
					for (var i=0; i<edit_comments.length; i++) {
						YE.addListener(edit_comments[i], 'click', function (e) {
							chk.add_comment_click(this, e);
						});
					}		    
				  
					// set all columns to the same color for a given item
					var div_view_items = document.getElementsByClassName('div_view_item');
					for (i = 0; i < div_view_items.length; i++) {
							var style = div_view_items[i].getAttribute('style');
							div_view_items[i].parentNode.parentNode.setAttribute('style',style);			    		
					}	    
				   
		//			window.onunload =  function(e) {
		//					M.mod_checkoutcome.send_update_grade_batch(true);
		//					M.mod_checkoutcome.send_update_count_batch(true);
		//			};
					
					// Student Counter //
					// Add event to counter + and - buttons
					this.updatecountlist = new Array();		    
					var plus = YD.getElementsByClassName('counter_plus');
					for (var i=0; i<plus.length; i++) {
						YE.addListener(plus[i], 'click', function(e) {
							var n = this.nextSibling.value;
							n++;
							this.nextSibling.value = n;			
							chk.update_count_server(this);
						});		    	
					}
					var minus = YD.getElementsByClassName('counter_minus');
					for (var i=0; i<minus.length; i++) {
						YE.addListener(minus[i], 'click', function(e) {	    		
							var n = this.previousSibling.value;
							n--;
							this.previousSibling.value = n;
							chk.update_count_server(this)
						});
					}
					// Add event to counter input text
					var inputs = YD.getElementsByClassName('counter_value');
					for (var i=0; i<inputs.length; i++) {
						YE.addListener(inputs[i], 'change', function(e) {		    		
								chk.update_count_server(this);
						});		    	
					}

					// set an image to the portfolio export submit buttons
					var spans = document.getElementsByClassName('exporttoportfolio');
					var input = spans[0].firstChild.lastChild.previousSibling;
						input.setAttribute('src','pix/folder_page_white.png');
						input.setAttribute('title',M.util.get_string('exportpdftoportfolio','checkoutcome'));
						input.setAttribute('type','image');
						input.setAttribute('value','submit');
					for (i = 1; i < spans.length; i++) {
						var input = spans[i].firstChild.lastChild.previousSibling;
						input.setAttribute('src','pix/folder_table.png');
						input.setAttribute('title',M.util.get_string('exportcategorytoportfolio','checkoutcome'));
						input.setAttribute('type','image');
						input.setAttribute("value","submit");			    	
					}	
					
				},
				
				init_view_items_teacher : function(Y, url, sesskey, cmid, checkoutcomeid, studentid, groupid, periodid) {			
					this.serverurl = url;
					this.sesskey = sesskey;
					this.cmid = cmid;
					this.checkoutcomeid = checkoutcomeid;
					if (studentid != null) {
						this.studentid = studentid;
					}
					this.groupid = groupid;
					this.periodid = periodid;
					
					var YE = YAHOO.util.Event;
					var YD = YAHOO.util.Dom;
					
					 // Add event to store teacher grading
					this.updategradelist = new Array();
					var selects = document.getElementsByTagName('select');
					for (var i=0; i<selects.length; i++) {
						YE.addListener(selects[i], 'change', function (e) {
							chk.select_change_teacher(this, e);
						});
					}
					
					// Add event to the teacher's add_comment button
					var add_teacher_comments = document.getElementsByName('add_teacher_comment');
					for (var i=0; i<add_teacher_comments.length; i++) {
						YE.addListener(add_teacher_comments[i], 'click', function (e) {
							chk.add_teacher_comment_click(this, e);
						});
					}
					
					// Add event to the teacher's edit comment button
					var edit_teacher_comments = document.getElementsByName('edit_teacher_comment');
					for (var i=0; i<edit_teacher_comments.length; i++) {
						YE.addListener(edit_teacher_comments[i], 'click', function (e) {
							chk.add_teacher_comment_click(this, e);
						});
					}	    
					
					// set all columns to the same color for a given item
					var div_view_items = document.getElementsByClassName("div_view_item");
					for (i = 0; i < div_view_items.length; i++) {
							var style = div_view_items[i].getAttribute("style");
							div_view_items[i].parentNode.parentNode.setAttribute("style",style);			    		
					}
					
				},
				
				init_view_display : function(Y, url, sesskey, cmid, checkoutcomeid, displaycolor) {
					
					this.serverurl = url;
					this.sesskey = sesskey;
					this.cmid = cmid;
					this.checkoutcomeid = checkoutcomeid;
					this.displaycolor = displaycolor;
					
					var YE = YAHOO.util.Event;
					
					var id_color = document.getElementById("id_colorcode");
					
					YE.addListener(id_color, 'click', function (e) {
						chk.show_color_picker();
					});	
					
				},
				
				init_view_periods : function() {			
					
					// hide checkbox end date
					//document.getElementById('id_enddate_enabled').style.display = "none";
					//document.getElementById('id_enddate_enabled').nextSibling.style.display = "none";
					
					var YE = YAHOO.util.Event;
					
					// Add event to checkbox start date
					var checkbox_startdate = document.getElementById('id_startdate_enabled');
					YE.addListener(checkbox_startdate, 'click', function (e) {
						var checkbox_enddate = document.getElementById('id_enddate_enabled');
						if (this.checked == true) {
							checkbox_enddate.checked = true;
						} else {
							checkbox_enddate.checked = false;
						}
						});
				},
				
				init_edit_items : function(Y, url, sesskey, cmid, checkoutcomeid, imgediturl, imgaddurl, imgdelurl) {			
					
					this.serverurl = url;
					this.sesskey = sesskey;
					this.cmid = cmid;
					this.checkoutcomeid = checkoutcomeid;
					
					// Define various event handlers for Dialog 
					var handleAddSubmit = function() {				
						var linkurl = document.getElementById('input_link').value;
						// check url
						if (!chk.isValidURL(linkurl)) {
							alert('URL is not valid');
							return;
						}
						var itemid = document.getElementById('itemid_link').value;
						var sname = document.getElementById('sname_link').value;
						//alert('linkurl : ' + linkurl + ' - itemid : ' + itemid);
						chk.send_update_link(itemid,linkurl,sname,imgediturl,imgaddurl,imgdelurl);				
					};
					var handleCancel = function() {
						this.cancel();
					};
					var handleDelSubmit = function() {				
						var itemid = document.getElementById('itemid_link').value;
						var sname = document.getElementById('sname_link').value;
						chk.send_delete_link(itemid,sname,imgaddurl);				
					};
					
					var addLinkDialog = new YAHOO.widget.Dialog("dialog_linkurl", {
						width: "600px", 
						fixedcenter: true, 
						constraintoviewport: true, 
						underlay: "shadow", 
						close: true, 
						visible: false, 
						draggable: true,
						buttons : [ { text:M.util.get_string('validate','checkoutcome'), handler:handleAddSubmit, isDefault:true },{ text:M.util.get_string('cancel','checkoutcome'), handler:handleCancel } ] 
					});
					
					var YE = YAHOO.util.Event;
					var YD = YAHOO.util.Dom;
					
					// Add event to open a popup by clicking on add link image
					var imgs = document.getElementsByClassName('resource');
					for (var i=0; i<imgs.length; i++) {
						YE.addListener(imgs[i], 'click', function (e) {
							//Get item shortname displayed in attribute id
							var id = this.getAttribute('id');
							var sname = id.substring(id.lastIndexOf("_") + 1);
							var itemid = id.substring(id.indexOf("_") + 1, id.lastIndexOf("_"));
							// get input value if existing
							var hreflink = YD.getAttribute(YD.get('resourcelink_'+itemid),'href');
							if (!hreflink) {
								hreflink = '';
							}
							// Set content
							addLinkDialog.setHeader(M.util.get_string('new_link_outcome','checkoutcome') + sname);
							addLinkDialog.setBody("URL : " + "<input id='input_link' type='text' name='link' size='70' value='" + hreflink +"'>"
									+ "<input id='itemid_link' type='hidden' name='itemid_link' value="+itemid+">"
									+ "<input id='sname_link' type='hidden' name='sname_link' value="+sname+">");
							addLinkDialog.cfg.setProperty("underlay","matte");					
							// Render
							addLinkDialog.render(document.body);
							addLinkDialog.show();
						});
					}
					
					var deleteLinkDialog = new YAHOO.widget.Dialog("dialog_deletelink", {
						width: "600px", 
						fixedcenter: true, 
						constraintoviewport: true, 
						underlay: "shadow", 
						close: true, 
						visible: false, 
						draggable: true,
						buttons : [ { text:M.util.get_string('validate','checkoutcome'), handler:handleDelSubmit, isDefault:true },{ text:M.util.get_string('cancel','checkoutcome'), handler:handleCancel } ] 
					});	
					// Add event to propose to delete link
					var imgsdel = document.getElementsByClassName('resourcedel');
					for (var i=0; i<imgsdel.length; i++) {
						YE.addListener(imgsdel[i], 'click', function (e) {
							//Get item shortname displayed in attribute id
							var id = this.getAttribute('id');
							var sname = id.substring(id.lastIndexOf("_") + 1);
							var itemid = id.substring(id.indexOf("_") + 1, id.lastIndexOf("_"));
							// Set content
							deleteLinkDialog.setHeader(M.util.get_string('delete_link_outcome','checkoutcome') + sname);
							deleteLinkDialog.setBody(M.util.get_string('delete_link_question','checkoutcome')
									+ "<input id='itemid_link' type='hidden' name='itemid_link' value=" + itemid + ">"
									+ "<input id='sname_link' type='hidden' name='sname_link' value=" + sname + ">");
							deleteLinkDialog.cfg.setProperty("underlay","matte");					
							// Render
							deleteLinkDialog.render(document.body);
							deleteLinkDialog.show();
						});
					}
					
					// Add event to counter + and - buttons
					this.updatecountgoallist = new Array();		    
					var plus = YD.getElementsByClassName('counter_plus');
					for (var i=0; i<plus.length; i++) {
						YE.addListener(plus[i], 'click', function(e) {
							var n = this.nextSibling.value;
							n++;
							this.nextSibling.value = n;			
							chk.update_countgoal_server(this);
						});		    	
					}
					var minus = YD.getElementsByClassName('counter_minus');
					for (var i=0; i<minus.length; i++) {
						YE.addListener(minus[i], 'click', function(e) {	    		
							var n = this.previousSibling.value;
							n--;
							this.previousSibling.value = n;
							chk.update_countgoal_server(this)
						});
					}
					// Add event to counter input text
					var inputs = YD.getElementsByClassName('counter_value');
					for (var i=0; i<inputs.length; i++) {
						YE.addListener(inputs[i], 'change', function(e) {		    		
								chk.update_countgoal_server(this);
						});		    	
					}
					
		//		    window.onunload =  function(e) {
		//				chk.send_update_countgoal_batch(true);
		//		    };
					
				},	
				
				isValidURL : function(url) {
					var urlRegxp = /^(http:\/\/|https:\/\/){1}/;
					if (urlRegxp.test(url) != true) {
						return false;
					} else {
						return true;
					}
				},
				   
				
				show_color_picker : function() {
					
					var fitem_id_colorcode = document.getElementById("fitem_id_colorcode");
					
					var id_color = document.getElementById("id_colorcode");
					// set input color code invisible
					id_color.style.display = "none";
					
					//fitem_id_colorcode.firstChild.nextSibling.removeChild(id_color);
					
					//create div felement
					var felement = document.createElement('div');
					felement.setAttribute('class','felement colorpicker');
					fitem_id_colorcode.appendChild(felement);
					
					//create span colorcontainer
					var color_container = document.createElement('span');
					color_container.setAttribute('id','colorcontainer');
					felement.appendChild(color_container);
					
					var picker = new YAHOO.widget.ColorPicker("colorcontainer", {
						showhsvcontrols: true,
						showhexcontrols: true,
						images: {
							PICKER_THUMB: "pix/picker_thumb.png",
							HUE_THUMB: "pix/hue_thumb.png"
						}
					});
					
					if (this.displaycolor != null) {
						picker.setValue(this.displaycolor, false);
					}			
					
					var onRgbChange = function(o) {
						var id_color = document.getElementById("id_colorcode");
						id_color.value = o.newValue;
					}			 
					//subscribe to the rgbChange event;
					picker.on("rgbChange", onRgbChange);
				},
				
				check_beforeunload:  function(e) {			
					if (this.updategradelist != null && this.updategradelist.length != 0) {
						if (confirm(M.util.get_string('savegrades','checkoutcome'))) {						
							chk.send_update_grade_batch(true);						
						}
						
					}
				},
				
				export_pdf_click: function(el, e) {
					
					var params = new Array();
					params.push('id='+this.cmid);
					params.push('sesskey='+this.sesskey);
					params = params.join('&');
					
					var callback= {
							success: function(o) {
							if (o.responseText != 'OK') {
								alert(o.responseText);
							}
							},
							failure: function(o) {
							alert(o.statusText);
							},
							timeout: 5000
						};

						var YC = YAHOO.util.Connect;
						YC.asyncRequest('POST', this.serverurl + '/export_pdf.php', callback, params);
				},
				
				add_comment_click: function(el, e) {			
			
					// set checkoutcomeitemid
					var itemid = (el.id).substring(12);
					
					// if div comment exists , get it, else create it
					var div_comment = null;
					if (document.getElementById('div_comment_' + itemid) != null) {
						div_comment = document.getElementById('div_comment_' + itemid);
					} else {
						// targeted div
						var div_ch_item = document.getElementById('ch_item_' + itemid);
						//create comment div
						div_comment = document.createElement('div');
						div_comment.setAttribute('id','div_comment_'+itemid);
						div_comment.setAttribute('class','div_comment');
						div_ch_item.insertBefore(div_comment, div_ch_item.firstChild.nextSibling.nextSibling);
					}		
					
					// if comment exists, get it
					var comment = null;
					if (document.getElementById('comment_text_' + itemid) != null) {
						comment = document.getElementById('comment_text_' + itemid).innerHTML;
					};			
					
					if (div_comment.hasChildNodes()) {
						while (div_comment.childNodes.length >= 1) {
							div_comment.removeChild(div_comment.firstChild); 
						} 
					}
					
					//////////////////// Create form for comment ///////////////////////////
					var comment_form = document.createElement('form');
					comment_form.setAttribute('action',this.serverurl + '/updatecomment.php#ch_item_'+itemid);
					comment_form.setAttribute('id','comment_form_' + itemid);
					comment_form.setAttribute('class','commentform');
					div_comment.appendChild(comment_form);
						
					// create and add input hidden action
					var input_action = document.createElement('input');
					input_action.setAttribute('type','hidden');
					input_action.setAttribute('name','action');
					input_action.setAttribute('value','addComment');
					comment_form.appendChild(input_action);
							
					// create and add input hidden itemid
					var input_itemid = document.createElement('input');
					input_itemid.setAttribute('type','hidden');
					input_itemid.setAttribute('name','itemid');
					input_itemid.setAttribute('value',itemid);
					comment_form.appendChild(input_itemid);
						
					// create and add input hidden cmid
					var input_cmid = document.createElement('input');
					input_cmid.setAttribute('type','hidden');
					input_cmid.setAttribute('name','checkoutcome');
					input_cmid.setAttribute('value',this.checkoutcomeid);
					comment_form.appendChild(input_cmid);
					
					// create and add input hidden periodid
					var input_periodid = document.createElement('input');
					input_periodid.setAttribute('type','hidden');
					input_periodid.setAttribute('name','selected_periodid');
					input_periodid.setAttribute('value',this.periodid);
					comment_form.appendChild(input_periodid);
							
					// create and add textarea
					var textare = document.createElement('textarea');
					textare.setAttribute('name','comment');
					textare.setAttribute('id','comment_textarea_' + itemid);
					textare.setAttribute('cols','80');
					textare.setAttribute('rows','6');
					textare.setAttribute('onkeypress','this.value=this.value.substr(0,999)');
					textare.setAttribute('onchange','this.value=this.value.substr(0,999)');
					textare.setAttribute('onblur','this.value=this.value.substr(0,999)');
					if (comment != null) {
						textare.innerHTML = comment;
					}
					comment_form.appendChild(textare);
							
					var br = document.createElement('br');
					comment_form.appendChild(br);
					
					// add comment about max length
					var max_length = document.createElement('p');
					var max_text = document.createTextNode(M.util.get_string('maxlength','checkoutcome'));
					max_length.appendChild(max_text);
					max_length.setAttribute('class','commentmaxlength');
					comment_form.appendChild(max_length);			
							
					// create and add input submit
					var subm = document.createElement('input');
					subm.setAttribute('type','submit');
					subm.setAttribute('value',M.util.get_string('validate','checkoutcome'));
					subm.setAttribute('id','submit_' + itemid);
					subm.setAttribute('title',M.util.get_string('validate','checkoutcome'));
					comment_form.appendChild(subm);
					
					/////////////////// Create form for cancel comment ///////////////////////////
					var cancel_comment_form = document.createElement('form');
					cancel_comment_form.setAttribute('action',this.serverurl + '/view.php#ch_item_'+itemid);
					cancel_comment_form.setAttribute('id','cancel_comment_form_' + itemid);
					cancel_comment_form.setAttribute('class','commentform');
					div_comment.appendChild(cancel_comment_form);
										
					// create and add input hidden checkoutcomeid
					var input_cmid = document.createElement('input');
					input_cmid.setAttribute('type','hidden');
					input_cmid.setAttribute('name','checkoutcome');
					input_cmid.setAttribute('value',this.checkoutcomeid);
					cancel_comment_form.appendChild(input_cmid);
					
					// create and add input hidden periodid
					var input_periodid = document.createElement('input');
					input_periodid.setAttribute('type','hidden');
					input_periodid.setAttribute('name','selected_periodid');
					input_periodid.setAttribute('value',this.periodid);
					cancel_comment_form.appendChild(input_periodid);
					
					// create and add input cancel
					var canc = document.createElement('input');
					canc.setAttribute('type','submit');
					canc.setAttribute('Value',M.util.get_string('cancel','checkoutcome'));
					canc.setAttribute('id','cancel_' + itemid);
					canc.setAttribute('title',M.util.get_string('cancel','checkoutcome'));
					cancel_comment_form.appendChild(canc);			

				},
				
				add_teacher_comment_click: function(el, e) {			
					
					var YE = YAHOO.util.Event;
					var YD = YAHOO.util.Dom;
					
					// set gradeitemid
					var itemid = (el.id).substring(20);
					
					// if div comment exists , get it, else create it
					var div_comment = null;
					if (document.getElementById('div_teacher_comment_' + itemid) != null) {
						div_comment = document.getElementById('div_teacher_comment_' + itemid);
					} else {
						// targeted div
						var div_ch_item = document.getElementById('ch_item_' + itemid);
						//create comment div
						div_comment = document.createElement('div');
						div_comment.setAttribute('id','div_teacher_comment_'+itemid);
						div_comment.setAttribute('class','div_comment');
						div_ch_item.appendChild(div_comment);
					}		
					
					// if comment exists, get it
					var comment = null;
					if (document.getElementById('teacher_comment_text_' + itemid) != null) {
						comment = document.getElementById('teacher_comment_text_' + itemid).innerHTML;
					};			
					
					if (div_comment.hasChildNodes()) {
						while (div_comment.childNodes.length >= 1) {
							div_comment.removeChild(div_comment.firstChild); 
						} 
					}
					
					//////////////////////////	 Create form for comment	/////////////////////////////////
					var comment_form = document.createElement('form');
					comment_form.setAttribute('action',this.serverurl + '/updatecomment.php#ch_item_'+itemid);
					comment_form.setAttribute('id','teacher_comment_form_' + itemid);
					comment_form.setAttribute('class','commentform');
					div_comment.appendChild(comment_form);
						
					// create and add input hidden action
					var input_action = document.createElement('input');
					input_action.setAttribute('type','hidden');
					input_action.setAttribute('name','action');
					input_action.setAttribute('value','addteacherComment');
					comment_form.appendChild(input_action);
					
					// create and add input hidden group
					var input_group = document.createElement('input');
					input_group.setAttribute('type','hidden');
					input_group.setAttribute('name','group');
					input_group.setAttribute('value', this.groupid);
					comment_form.appendChild(input_group);
					
					// create and add input hidden studentid
					var input_studentid = document.createElement('input');
					input_studentid.setAttribute('type','hidden');
					input_studentid.setAttribute('name','studentid');
					input_studentid.setAttribute('value',this.studentid);
					comment_form.appendChild(input_studentid);
							
					// create and add input hidden itemid
					var input_itemid = document.createElement('input');
					input_itemid.setAttribute('type','hidden');
					input_itemid.setAttribute('name','itemid');
					input_itemid.setAttribute('value',itemid);
					comment_form.appendChild(input_itemid);
						
					// create and add input hidden checkoutcomeid
					var input_cmid = document.createElement('input');
					input_cmid.setAttribute('type','hidden');
					input_cmid.setAttribute('name','checkoutcome');
					input_cmid.setAttribute('value',this.checkoutcomeid);
					comment_form.appendChild(input_cmid);
					
					// create and add input hidden periodid
					var input_periodid = document.createElement('input');
					input_periodid.setAttribute('type','hidden');
					input_periodid.setAttribute('name','selected_periodid');
					input_periodid.setAttribute('value',this.periodid);
					comment_form.appendChild(input_periodid);
							
					// create and add textarea
					var textare = document.createElement('textarea');
					textare.setAttribute('name','teacher_comment');
					textare.setAttribute('id','teacher_comment_textarea_' + itemid);
					textare.setAttribute('cols','80');
					textare.setAttribute('rows','6');
					textare.setAttribute('onkeypress','this.value=this.value.substr(0,999)');
					textare.setAttribute('onchange','this.value=this.value.substr(0,999)');
					textare.setAttribute('onblur','this.value=this.value.substr(0,999)');
					if (comment != null) {
						textare.innerHTML = comment;
					}
					comment_form.appendChild(textare);
							
					var br = document.createElement('br');
					comment_form.appendChild(br);
				
					// add comment about max length
					var max_length = document.createElement('p');
					var max_text = document.createTextNode(M.util.get_string('maxlength','checkoutcome'));
					max_length.appendChild(max_text);
					max_length.setAttribute('class','commentmaxlength');
					comment_form.appendChild(max_length);			
					
					// create and add input submit
					var subm = document.createElement('input');
					subm.setAttribute('type','submit');
					subm.setAttribute('value',M.util.get_string('validate','checkoutcome'));
					subm.setAttribute('id','submit_teacher_' + itemid);
					subm.setAttribute('title',M.util.get_string('validate','checkoutcome'));
					comment_form.appendChild(subm);	
					
		//			var validate = YD.get('submit_teacher_' + itemid);
		//			YE.addListener(validate, 'click', function (e) {
		//				
		//			});
					
					////////////////// 	Create form for cancel comment	//////////////////////////////////////
					var cancel_comment_form = document.createElement('form');
					cancel_comment_form.setAttribute('action',this.serverurl + '/view.php#ch_item_'+itemid);
					cancel_comment_form.setAttribute('id','cancel_teacher_comment_form_' + itemid);
					cancel_comment_form.setAttribute('class','commentform');
					div_comment.appendChild(cancel_comment_form);
					
					// create and add input hidden studentid
					var input_studentid = document.createElement('input');
					input_studentid.setAttribute('type','hidden');
					input_studentid.setAttribute('name','studentid');
					input_studentid.setAttribute('value',this.studentid);
					cancel_comment_form.appendChild(input_studentid);
					
					// create and add input hidden group
					var input_group = document.createElement('input');
					input_group.setAttribute('type','hidden');
					input_group.setAttribute('name','group');
					input_group.setAttribute('value', this.groupid);
					cancel_comment_form.appendChild(input_group);
							
					// create and add input hidden checkoutcomeid
					var input_cmid = document.createElement('input');
					input_cmid.setAttribute('type','hidden');
					input_cmid.setAttribute('name','checkoutcome');
					input_cmid.setAttribute('value',this.checkoutcomeid);
					cancel_comment_form.appendChild(input_cmid);
					
					// create and add input hidden periodid
					var input_periodid = document.createElement('input');
					input_periodid.setAttribute('type','hidden');
					input_periodid.setAttribute('name','selected_periodid');
					input_periodid.setAttribute('value',this.periodid);
					cancel_comment_form.appendChild(input_periodid);
					
					// add input hidden itemid
					var input_itemid = document.createElement('input');
					input_itemid.setAttribute('type','hidden');
					input_itemid.setAttribute('name','itemid');
					input_itemid.setAttribute('value',itemid);
					cancel_comment_form.appendChild(input_itemid);
					
					// create and add input cancel
					var canc = document.createElement('input');
					canc.setAttribute('type','submit');
					canc.setAttribute('Value',M.util.get_string('cancel','checkoutcome'));
					canc.setAttribute('id','cancel_teacher_' + itemid);
					canc.setAttribute('title',M.util.get_string('cancel','checkoutcome'));
					cancel_comment_form.appendChild(canc);			
					

				},
				
				select_change: function(el, e) {
					// Save check to list for updating
					this.update_grade_server(el.id, el.value);
					
				},
				
				select_change_teacher: function(el, e) {
					// Save check to list for updating
					this.update_gradelist_teacher(el.id, el.value);
					
				},
				
				update_grade_server: function(id, grade) {
					
					var itemid = '-1';
					if (id.length > 7) {
						itemid = id.substring(7);
					}
					for (var i=0; i<this.updategradelist.length; i++) {
						if (this.updategradelist[i].itemid == itemid) {
								if (this.updategradelist[i].grade != grade) {
									this.updategradelist.splice(i, 1);
								}
								return;
						}
					}

					this.updategradelist.push({'itemid':itemid, 'grade':grade});

					if (this.updategradetimeout) {
						clearTimeout(this.updategradetimeout);
					}
					this.updategradetimeout = setTimeout(function() {
						chk.send_update_grade_batch(false);
					}, 1000);
				},		
				
				update_gradelist_teacher: function(id, grade) {
					
					var itemid = '-1';
					if (id.length > 7) {
						itemid = id.substring(7);
					}
					for (var i=0; i<this.updategradelist.length; i++) {
						if (this.updategradelist[i].itemid == itemid) {
								if (this.updategradelist[i].grade != grade) {
									this.updategradelist.splice(i, 1);
								}
								return;
						}
					}
					
					this.updategradelist.push({'itemid':itemid, 'grade':grade});
					
					if (this.updategradetimeout) {
						clearTimeout(this.updategradetimeout);
					}
					this.updategradetimeout = setTimeout(function() {
						chk.send_update_grade_teacher_batch(false);
					}, 1000);
					
				},
				
				update_countgoal_server: function(el) {
					
					var itemid = '-1';						
					var countgoal = '-1';
					var image_type = el.id.substring(8,11);
					if (image_type == 'plu') {
						itemid = el.id.substring(12);
						countgoal = el.nextSibling.value;
					} else if (image_type == 'min'){
						itemid = el.id.substring(12);
						countgoal = el.previousSibling.value;
					} else {
						itemid = el.id.substring(13);
						countgoal = el.value;				
					}
					
					//alert ("id : " + itemid + " - type : " + image_type + " - countgoal : " + countgoal);
					
					for (var i=0; i<this.updatecountgoallist.length; i++) {
						if (this.updatecountgoallist[i].itemid == itemid) {
								if (this.updatecountgoallist[i].countgoal != countgoal) {
									this.updatecountgoallist.splice(i, 1);
								}
								//return;
						}
					}		
					
					this.updatecountgoallist.push({'itemid':itemid, 'countgoal':countgoal});

					if (this.updatecountgoaltimeout != null) {
						clearTimeout(this.updatecountgoaltimeout);
						//this.updatecountgoaltimeout = null;
					}
					this.updatecountgoaltimeout = setTimeout(function() {
						chk.send_update_countgoal_batch(false);
					}, 1000);
				},
				
				update_count_server: function(el) {
					
					var itemid = '-1';						
					var count = '-1';
					var image_type = el.id.substring(8,11);
					if (image_type == 'plu') {
						itemid = el.id.substring(12);
						count = el.nextSibling.value;
					} else if (image_type == 'min'){
						itemid = el.id.substring(12);
						count = el.previousSibling.value;
					} else {
						itemid = el.id.substring(13);
						count = el.value;				
					}
					
					//alert ("TEST");
					
					for (var i=0; i<this.updatecountlist.length; i++) {
						if (this.updatecountlist[i].itemid == itemid) {
								if (this.updatecountlist[i].count != count) {
									this.updatecountlist.splice(i, 1);
								}
								//return;
						}
					}		
					
					this.updatecountlist.push({'itemid':itemid, 'count':count});

					if (this.updatecounttimeout != null) {
						clearTimeout(this.updatecounttimeout);
						//this.updatecountgoaltimeout = null;
					}
					this.updatecounttimeout = setTimeout(function() {
						chk.send_update_count_batch(false);
					}, 1000);
				},
				
				send_update_grade_batch: function(unload) {
					// Send all updates after 1 second of inactivity (or on page unload)
					if (this.updategradetimeout) {
						clearTimeout(this.updategradetimeout);
						this.updategradetimeout = null;
					}

					if (this.updategradelist.length == 0) {
						return;
					}

					var params = new Array();
					var items = new Array();
					for (var i=0; i<this.updategradelist.length; i++) {
						items[this.updategradelist[i].itemid] = this.updategradelist[i].grade;
						params.push('items['+this.updategradelist[i].itemid+']='+this.updategradelist[i].grade);
					}
					params.push('studentid='+this.studentid);
					params.push('sesskey='+this.sesskey);
					params.push('id='+this.cmid);
					params.push('selected_periodid='+this.periodid);
					params = params.join('&');

					// Clear the list of updates to send
					this.updategradelist = new Array();

					// Send message to server
					if (!unload) {
					var callback= {
						success: function(o) {
							
							var YD = YAHOO.util.Dom;
							var ajaxLoader = YD.get('ajaxLoadPanel_c');
							var body = YD.getAncestorByTagName(ajaxLoader,'body');			
							body.removeChild(ajaxLoader);
							
							var now = new Date();
							var date = chk.get_formatted_date(now,'fr');
							for (var itemid in items) {				    
								var sel = YD.get('selstu_' + itemid);
								
								// add date info
								if (YD.getNextSibling(sel) != null) {
									YD.getNextSibling(sel).innerHTML = '[' + date + ']';
								} else {
									var dateinfo = document.createElement('div');
									dateinfo.className = 'isgraded';
									dateinfo.innerHTML = '[' + date + ']';					
									YD.insertAfter(dateinfo,sel);
								}
								
							}
							var lastdate = YD.get('lastdatestudent');
							lastdate.innerHTML = M.util.get_string('lastdatestudent','checkoutcome') + date;					
						},
						failure: function(o) {
							alert(o.statusText);
						},
						timeout: 5000
					};
					
					var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
							width:"240px",
							fixedcenter:true,
							close:true,
							draggable:false,
							modal:false,
							zIndex:9,
							visible:false,
							effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
							}
							);
					var YC = YAHOO.util.Connect;
					YC.asyncRequest('POST', this.serverurl + '/updateselects.php', callback, params);
					
					ajaxLoadingPanel.setHeader("Saving, please wait...");
					ajaxLoadingPanel.setBody(' ');
					ajaxLoadingPanel.render(document.body);
					ajaxLoadingPanel.show();
					
					} else {
					// Nasty hack to make it save everything on unload
					var beacon = new Image();
					beacon.src = this.serverurl + '/updateselects.php' + '?' + params;
					}
					
				   
				},
				
				send_update_grade_teacher_batch: function(unload) {
					// Send all updates after 1 second of inactivity (or on page unload)
					if (this.updategradetimeout) {
						clearTimeout(this.updategradetimeout);
						this.updategradetimeout = null;
					}

					if (this.updategradelist.length == 0) {
						return;
					}

					var params = new Array();
					var items = new Array();
					for (var i=0; i<this.updategradelist.length; i++) {
						items[this.updategradelist[i].itemid] = this.updategradelist[i].grade;
						params.push('items['+this.updategradelist[i].itemid+']='+this.updategradelist[i].grade);
					}
					params.push('studentid='+this.studentid);
					params.push('sesskey='+this.sesskey);
					params.push('id='+this.cmid);
					params.push('selected_periodid='+this.periodid);
					params = params.join('&');

					// Clear the list of updates to send
					this.updategradelist = new Array();

					// Send message to server
					if (!unload) {
					var callback= {
						success: function(o) {
							
							var YD = YAHOO.util.Dom;
							var ajaxLoader = YD.get('ajaxLoadPanel_c');
							var body = YD.getAncestorByTagName(ajaxLoader,'body');			
							body.removeChild(ajaxLoader);
							
							var now = new Date();
							var date = chk.get_formatted_date(now,'fr');
							for (var itemid in items) {	
								var sel = YD.get('seltea_' + itemid);
								
								// add date info
								if (YD.getNextSibling(sel) != null) {
									YD.getNextSibling(sel).innerHTML = '[' + date + ']';
								} else {
									var dateinfo = document.createElement('div');
									dateinfo.className = 'isgraded';
									dateinfo.innerHTML = '[' + date + ']';					
									YD.insertAfter(dateinfo,sel);
								}
								
							}
							var lastdate = YD.get('lastdateteacher');
							lastdate.innerHTML = M.util.get_string('lastdateteacher','checkoutcome') + date;		
						},
						failure: function(o) {
							alert(o.statusText);
						},
						timeout: 5000
					};
					
					var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
							width:"240px",
							fixedcenter:true,
							close:true,
							draggable:false,
							modal:false,
							zIndex:9,
							visible:false,
							effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
							}
							);
					var YC = YAHOO.util.Connect;
					YC.asyncRequest('POST', this.serverurl + '/updateselects.php', callback, params);
					
					ajaxLoadingPanel.setHeader("Saving, please wait...");
					ajaxLoadingPanel.setBody(' ');
					ajaxLoadingPanel.render(document.body);
					ajaxLoadingPanel.show();
					
					} else {
					// Nasty hack to make it save everything on unload
					var beacon = new Image();
					beacon.src = this.serverurl + '/updateselects.php' + '?' + params;
					}
					
				   
				},
				
				send_update_countgoal_batch: function(unload) {
				   // Send all updates after 1 second of inactivity (or on page unload)
					if (this.updatecountgoaltimeout) {
						clearTimeout(this.updatecountgoaltimeout);
						this.updatecountgoaltimeout = null;
					}

					if (this.updatecountgoallist.length == 0) {
						return;
					}

					var params = new Array();
					for (var i=0; i<this.updatecountgoallist.length; i++) {
						params.push('items['+this.updatecountgoallist[i].itemid+']='+this.updatecountgoallist[i].countgoal);
					}
					params.push('sesskey='+this.sesskey);
					params.push('id='+this.cmid);
					params.push('action=updateCountgoals');		    
					params = params.join('&');

					// Clear the list of updates to send
					this.updatecountgoallist = new Array();

					// Send message to server
					if (!unload) {
					var callback= {
						success: function(o) {			    
								var YD = YAHOO.util.Dom;
								var ajaxLoader = YD.get('ajaxLoadPanel_c');
								var body = YD.getAncestorByTagName(ajaxLoader,'body');					
								body.removeChild(ajaxLoader);
						},
						failure: function(o) {
							alert(o.statusText);
							var YD = YAHOO.util.Dom;
							var ajaxLoader = YD.get('ajaxLoadPanel_c');
							var body = YD.getAncestorByTagName(ajaxLoader,'body');					
							body.removeChild(ajaxLoader);
						},
						timeout: 5000
					};
					
					var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
							width:"240px",
							fixedcenter:true,
							close:true,
							draggable:false,
							modal:false,
							zIndex:9,
							visible:false,
							effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
							}
							);
					 
					var YC = YAHOO.util.Connect;
					YC.asyncRequest('POST', this.serverurl + '/edit.php', callback, params);
					
					ajaxLoadingPanel.setHeader("Saving, please wait...");
					ajaxLoadingPanel.setBody(' ');
					ajaxLoadingPanel.render(document.body);
					ajaxLoadingPanel.show();
					
					} else {
					// Nasty hack to make it save everything on unload
					var beacon = new Image();
					beacon.src = this.serverurl + '/edit.php' + '?' + params;
					}
					
				   
				},
				
				send_update_count_batch: function(unload) {
					   // Send all updates after 1 second of inactivity (or on page unload)
						if (this.updatecounttimeout) {
							clearTimeout(this.updatecounttimeout);
							this.updatecounttimeout = null;
						}

						if (this.updatecountlist.length == 0) {
							return;
						}

						var params = new Array();
						for (var i=0; i<this.updatecountlist.length; i++) {
							params.push('items['+this.updatecountlist[i].itemid+']='+this.updatecountlist[i].count);
						}
						params.push('sesskey='+this.sesskey);
						params.push('id='+this.cmid);
						params.push('selected_periodid='+this.periodid);
						params.push('action=updateCounts');		    
						params = params.join('&');

						// Clear the list of updates to send
						this.updatecountlist = new Array();

						// Send message to server
						if (!unload) {
						var callback= {
							success: function(o) {			    
									var YD = YAHOO.util.Dom;
									var ajaxLoader = YD.get('ajaxLoadPanel_c');
									var body = YD.getAncestorByTagName(ajaxLoader,'body');					
									body.removeChild(ajaxLoader);
							},
							failure: function(o) {
								alert(o.statusText);
								var YD = YAHOO.util.Dom;
								var ajaxLoader = YD.get('ajaxLoadPanel_c');
								var body = YD.getAncestorByTagName(ajaxLoader,'body');					
								body.removeChild(ajaxLoader);
							},
							timeout: 5000
						};
						
						var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
								width:"240px",
								fixedcenter:true,
								close:true,
								draggable:false,
								modal:false,
								zIndex:9,
								visible:false,
								effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
								}
								);
						 
						var YC = YAHOO.util.Connect;
						YC.asyncRequest('POST', this.serverurl + '/view.php', callback, params);
						
						ajaxLoadingPanel.setHeader("Saving, please wait...");
						ajaxLoadingPanel.setBody(' ');
						ajaxLoadingPanel.render(document.body);
						ajaxLoadingPanel.show();
						
						} else {
						// Nasty hack to make it save everything on unload
						var beacon = new Image();
						beacon.src = this.serverurl + '/view.php' + '?' + params;
						}
				},
				
				get_formatted_date: function(date,lang) {
					if (lang == null) {
						lang = 'en';
					}
					var month = date.getMonth();
					var day = date.getDate();
					var year = date.getFullYear();
					var hours = date.getHours();
					var minutes = date.getMinutes();		

					hours = chk.formatHM(hours);
					minutes = chk.formatHM(minutes);

					var en = ['Jan','Feb','Mar','Apr','Mai','Jun','Jul','Aug','Sept','Oct','Nov','Dec'];
					var fr = ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Aou','Sept','Oct','Nov','Déc'];

					var months = new Array();
					months['en'] = en;
					months['fr'] = fr;

					return day + '-' + months[lang][month] + '-' + year + ' ' + hours + ':' + minutes;

				},
				
				formatHM: function(num) {
					if (num < 10) {
						num = '0' + num.toString();
					}
					return num.toString();
				},		
				
				send_update_link: function(itemid,linkurl, sname, imgediturl, imgaddurl,imgdelurl) {    
					
					var params = new Array();
					params.push('itemid='+itemid);
					params.push('linkurl='+linkurl);
					params.push('action=updateLink');
					params.push('sesskey='+this.sesskey);
					params.push('id='+this.cmid);
					params.push('checkoutcomeid='+this.checkoutcomeid);
					params = params.join('&');		    
					
					// Send message to server
					var callback= {
					   success: function(o) {
							var YD = YAHOO.util.Dom;					
							var ajaxLoader = YD.get('ajaxLoadPanel_c');
							var dialog = YD.get('dialog_linkurl_c');	
							var body = YD.getAncestorByTagName(dialog,'body');					
							body.removeChild(ajaxLoader);
							body.removeChild(dialog);					
							
							//replace new link picture with edit link picture
							var id = 'resource_' + itemid + '_' + sname;
							var img = YD.get(id);
							YD.setAttribute(img,'src',imgediturl);
							YD.setAttribute(img,'title',M.util.get_string('edit_link','checkoutcome'));
							YD.setAttribute(img,'alt',M.util.get_string('edit_link','checkoutcome'));
							// add delete link picture
							var delimg = document.createElement('img');
							delimg.id = 'resourcedel_' + itemid + '_' + sname;
							delimg.title = M.util.get_string("delete_link","checkoutcome");
							delimg.alt = M.util.get_string("delete_link","checkoutcome");
							delimg.className = 'resourcedel';
							delimg.src = imgdelurl;					
							YD.insertAfter(delimg,img);
							//add event to the delete picture
										var handleCancel = function() {
											this.cancel();
										};
										var handleDelSubmit = function() {				
											var itemid = document.getElementById('itemid_link').value;
											var sname = document.getElementById('sname_link').value;
											chk.send_delete_link(itemid,sname,imgaddurl);				
										};
										var deleteLinkDialog = new YAHOO.widget.Dialog("dialog_deletelink", {
												width: "600px", 
												fixedcenter: true, 
												constraintoviewport: true, 
												underlay: "shadow", 
												close: true, 
												visible: false, 
												draggable: true,
												buttons : [ { text:M.util.get_string('validate','checkoutcome'), handler:handleDelSubmit, isDefault:true },{ text:M.util.get_string('cancel','checkoutcome'), handler:handleCancel } ] 
											});
											var YE = YAHOO.util.Event;
											// Add event to propose to delete link
											var imgsdel = document.getElementsByClassName('resourcedel');
											for (var i=0; i<imgsdel.length; i++) {
												YE.addListener(imgsdel[i], 'click', function (e) {
													//Get item shortname displayed in attribute id
													var id = this.getAttribute('id');
													var sname = id.substring(id.lastIndexOf("_") + 1);
													var itemid = id.substring(id.indexOf("_") + 1, id.lastIndexOf("_"));
													// Set content
													deleteLinkDialog.setHeader(M.util.get_string('delete_link_outcome','checkoutcome') + sname);
													deleteLinkDialog.setBody(M.util.get_string('delete_link_question','checkoutcome')
															+ "<input id='itemid_link' type='hidden' name='itemid_link' value=" + itemid + ">"
															+ "<input id='sname_link' type='hidden' name='sname_link' value=" + sname + ">");
													deleteLinkDialog.cfg.setProperty("underlay","matte");					
													// Render
													deleteLinkDialog.render(document.body);
													deleteLinkDialog.show();
												});
											}					
							
							// add link to fullname in table
							var hreflink = YD.get('resourcelink_' + itemid);
							if (!hreflink) {
								var tr = YD.getAncestorByTagName(img,'tr');
								var td = YD.getChildren(tr)[2];
								var tdvalue = td.innerHTML;
								td.innerHTML = '<a href="'+ linkurl +'" id="resourcelink_' + itemid + '" target="_new">' + tdvalue + '</a>';					
							} else {
								YD.setAttribute(hreflink,'href',linkurl);
							}
								
						},
						failure: function(o) {
							alert(o.statusText);
						},
						timeout: 10000
					};
					
					var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
						width:"240px",
						fixedcenter:true,
						close:true,
						draggable:false,
						modal:false,
						zIndex:9,
						visible:false,
						effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
						}
						);

					var YC = YAHOO.util.Connect;
					YC.asyncRequest('POST', this.serverurl + '/edit.php', callback, params);
					
					ajaxLoadingPanel.setHeader("Loading, please wait...");
					ajaxLoadingPanel.setBody(' ');
					ajaxLoadingPanel.render(document.body);
					ajaxLoadingPanel.show();
					
				},
				
				send_delete_link : function(itemid, sname, imgaddurl) {
					
					var params = new Array();
					params.push('itemid='+itemid);
					params.push('action=deleteLink');
					params.push('sesskey='+this.sesskey);
					params.push('id='+this.cmid);
					params.push('checkoutcomeid='+this.checkoutcomeid);
					params = params.join('&');
					
				 // Send message to server
					var callback= {
					   success: function(o) {
							var YD = YAHOO.util.Dom;
							
							// close popups
							var ajaxLoader = YD.get('ajaxLoadPanel_c');
							var dialog = YD.get('dialog_deletelink_c');
							dialog.parentNode.removeChild(ajaxLoader);
							dialog.parentNode.removeChild(dialog);
							//remove delete link picture
							var id_del = 'resourcedel_' + itemid + '_' + sname;
							var img_del = YD.get(id_del);
							//td = YD.getAncestorByTagName(img_del,'td');
							var img = YD.getPreviousSibling(img_del);
							img_del.parentNode.removeChild(img_del);
							// change edit link picture to new link picture					
							YD.setAttribute(img,'src',imgaddurl);
							YD.setAttribute(img,'title',M.util.get_string('add_link','checkoutcome'));
							YD.setAttribute(img,'alt',M.util.get_string('add_link','checkoutcome'));
							// remove link to fullname in table
							var hreflink = YD.get('resourcelink_' + itemid);
							var link_content = hreflink.innerHTML;
							var tr = YD.getAncestorByTagName(img,'tr');
							var td = YD.getChildren(tr)[2];
							td.innerHTML = link_content;						
						},
						failure: function(o) {
							alert(o.statusText);
						},
						timeout: 10000
					};
					
					var ajaxLoadingPanel = new YAHOO.widget.Panel("ajaxLoadPanel", {
						width:"240px",
						fixedcenter:true,
						close:true,
						draggable:false,
						modal:false,
						zIndex:9,
						visible:false,
						effect:{effect:YAHOO.widget.ContainerEffect.FADE, duration:0.5}
						}
						);

					var YC = YAHOO.util.Connect;
					YC.asyncRequest('POST', this.serverurl + '/edit.php', callback, params);
					
					ajaxLoadingPanel.setHeader("Loading, please wait...");
					ajaxLoadingPanel.setBody(' ');
					ajaxLoadingPanel.render(document.body);
					ajaxLoadingPanel.show();
					
				},

				enableDefaults : function() {
					var checkbox = document.getElementById('enable_defaults');
					var divDefaults = document.getElementById('define_defaults');
				
					if (checkbox.checked == true) {
						divDefaults.innerHTML = this.defaults;
					} else {
						divDefaults.innerHTML = '';
					}
				},
				
				check : function() {
					var checkbox = document.getElementById('check_uncheck_all');
					if (checkbox.checked == true) {
						this.checkall();
					} else {
						this.uncheckall();
					}
				},
				
				uncheckall : function() {
					var inputs = document.getElementsByTagName('input');
					var exp = /^out/;
					for(var i = 0; i < inputs.length; i++) {
						if (exp.test(inputs[i].name)) {
							inputs[i].checked = false;
						}
					}
				},

				checkall : function() {
					var inputs = document.getElementsByTagName('input');
					var exp = /^out/;
					for(var i = 0; i < inputs.length; i++) {
						if (exp.test(inputs[i].name)) {
							inputs[i].checked = true;
						}
					}
				},
				
				expand : function(index) {
					//alert("category" + index);
					var category = document.getElementById('category'+index);
					var img = document.getElementById('img_categ'+index);
					
					if (category.style.display != 'none') {
						category.style.display = 'none';
						img.src = 'pix/switch_plus.png';
					} else {
						category.style.display = 'block';
						img.src = 'pix/switch_minus.png';
					}
				},   
			};
			chk.init(Y, url, sesskey, cmid, checkoutcomeid, periodid, studentid, groupid, imgediturl, imgaddurl, imgdelurl, displaycolor, test);
		});
	}
};