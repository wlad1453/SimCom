/********************************************************************
 *                Copyright Simcom(shanghai)co. Ltd.                   *
 *---------------------------------------------------------------------
 * FileName      :   app_demo_gpio.c
 * version       :   0.10
 * Description   :   
 * Authors       :   fangshengchang
 * Notes         :
 *---------------------------------------------------------------------
 *
 *    HISTORY OF CHANGES
 *---------------------------------------------------------------------
 *0.10  2012-09-24, fangshengchang, create originally.
 *0.20  2013-03-26, maobin, modify the PIN definition, to adapt to the SIM800W and SIM800V
 *
 *--------------------------------------------------------------------
 * File Description
 * AT+CEAT=parma1,param2
 * param1 param2 
 *   1      1    Write the EAT_GPIO_TEST1,EAT_GPIO_TEST2 pin LEVEL_LOW 
 *   1      2    Write the EAT_GPIO_TEST1,EAT_GPIO_TEST2 pin LEVEL_HIGH
 *   1      3    Read the EAT_GPIO_TEST1,EAT_GPIO_TEST2 pin 
 *
 *   2      1     eat_lcd_light_sw(EAT_TRUE); eat_kpled_sw(EAT_TRUE);
 *   2      2     eat_lcd_light_sw(EAT_FALSE);eat_kpled_sw(EAT_FALSE);
 *   
 *   3      1     NETLIGHT on
 *   3      2     NETLIGHT off
 
 *--------------------------------------------------------------------
 ********************************************************************/
 
/* Include Files */
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <cstring>
#include "eat_modem.h"
#include "eat_interface.h"
#include "eat_periphery.h"
#include "eat_uart.h"
#include "eat_timer.h"

#include "eat_clib_define.h" //only in main.c

/* Types */
typedef void (*app_user_func)(void*);

/* External Functions declaration */
extern void APP_InitRegions(void);
extern void (* const eat_lcd_light_sw)(eat_bool sw, EatBLStep_enum step); // Transferred from eat_periphery.h 190820

/* Local Function declaration */
void app_main(void *data);
void app_func_ext1(void *data);
void app_user1(void* data);					// Process User1 declaration.
void show_time( EatRtc_st* data, char *msg);
void show_rtc_mem (char* data); 
void atcmd_to_modem(char* at_cmd, EatEvent_st *event);

/* Local Function */
#pragma arm section rodata = "APP_CFG"
APP_ENTRY_FLAG 
#pragma arm section rodata

#pragma arm section rodata="APPENTRY"
	const EatEntry_st AppEntry = 
	{
		app_main,
		app_func_ext1,
		(app_user_func)app_user1,  //app_user1,
		(app_user_func)EAT_NULL,  //app_user2,
		(app_user_func)EAT_NULL,  //app_user3,
		(app_user_func)EAT_NULL,  //app_user4,
		(app_user_func)EAT_NULL,  //app_user5,
		(app_user_func)EAT_NULL,  //app_user6,
		(app_user_func)EAT_NULL,  //app_user7,
		(app_user_func)EAT_NULL,  //app_user8,
		EAT_NULL,
		EAT_NULL,
		EAT_NULL,
		EAT_NULL,
		EAT_NULL,
		EAT_NULL
	};
#pragma arm section rodata

u32 g_pin_set_result = 0;

typedef struct {
	u32 width;					// Width of the echo impulse in uS
	u32 waiting_time;			// Time in uS between rear front of the ping (10 mS) and the fore front of the echo impulse
	eat_bool waiting_exceed;
	eat_bool width_exceed;	
} echo_pulse;

typedef struct {				// structure for an AT command
	u8 id;
	char atcmd[50];
	char answ_positive[10];
	char answ_negative[10];
	u8 param1;
	u8 param2;
} at_cmd;

typedef struct {
	u8 id;	
	eat_bool occupied;
	u8 H, M, S;
	u8 Y, mon, D;
	u16 dist_cm;	
} box;

const at_cmd gprs_su[7] = {													// Array of GPRS set up commands
	1,	"AT+SAPBR=0,1\r\n", "OK", "ERROR", NULL, NULL,							// disconnect GPRS service
	2,	"AT+CCALR?\r\n", "", "", 1, 0,											// Check whether there is mobile network
	3,	"AT+SAPBR = 3,1,\"CONTYPE\",\"GPRS\"\r\n", "OK", "ERROR", NULL, NULL,	// Connection type - GPRS
	4,	"AT+SAPBR = 3,1,\"APN\",\"internet\"\r\n", "OK", "ERROR", NULL, NULL,  	// APN internet provider
	5,	"AT+SAPBR = 3,1,\"USER\",\"\"\r\n", "OK", "ERROR", NULL, NULL,			// USER name / login if any
	6, 	"AT+SAPBR = 3,1,\"PWD\",\"\"\r\n", "OK", "ERROR", NULL, NULL,			// password for the GPRS services
	7,	"AT+SAPBR = 1,1\r\n", "OK", "ERROR", NULL, NULL							// connect to the GPRS network
};

const at_cmd http_su[7] = {												// Array of HTTP commands
	1,	"AT+HTTPINIT\r\n",	"OK", "ERROR", NULL, NULL,						// Initialization of HTTP session
	2,	"AT+HTTPPARA=\"CID\",1\r\n",  "OK", "", NULL, NULL,					// Set CID parameter for the HTTP session
	3,	"AT+HTTPPARA=\"URL\",\"https://site.ru/123.html\"\r\n",	"OK", "", NULL, NULL, // URL line to be send
	4,	"AT+HTTPACTION=0\r\n",	"", "", NULL, NULL,							// activating of GET method
	5,	"AT+HTTPREAD\r\n",	"", "", NULL, NULL,								// reading of server answer
	6,	"AT+HTTPTERM\r\n",	"", "", NULL, NULL,								// terminating the HTTP-session
	7, 	"AT+HTTPSTATUS?\r\n", "GET,0,0,0", "", NULL, NULL,							// status of send\recieve process	
};  

void app_func_ext1(void *data)
{
   u32 ret_val=0;
	/* This function can be called before Task running ,configure the GPIO,uart and etc.
	   Only these api can be used:
		 eat_uart_set_debug: 	set debug port
		 eat_pin_set_mode: 		set GPIO mode
		 eat_uart_set_at_port: 	set AT port
	*/
    eat_uart_set_debug (EAT_UART_2);		// UART2 is as DEBUG PORT
	eat_uart_set_debug (EAT_UART_USB);
    eat_uart_set_at_port(EAT_UART_1);	// UART1 is as AT PORT
	
	ret_val =ret_val<<1||eat_pin_set_mode(EAT_PIN11_GPIO17, EAT_PIN_MODE_GPIO);  // My entry 190820
    ret_val =ret_val<<1||eat_pin_set_mode(EAT_PIN13_GPIO19, EAT_PIN_MODE_GPIO);	
    
    g_pin_set_result = ret_val;
 }  // app_func_ext1(void *data)
 
eat_bool eat_modem_data_parse(u8* buffer, u16 len, u8* param1, u8* param2)       // AT+CEAT=param1,param2
{
    eat_bool ret_val = EAT_FALSE;
    u8* buf_ptr = NULL;
    /*param:%d,extern_param:%d*/
     buf_ptr = (u8*)strstr((const char *)buffer,"param");
    if( buf_ptr != NULL)
    {
        sscanf((const char *)buf_ptr, "param:%d,extern_param:%d",(int*)param1, (int*)param2);
        eat_trace("data parse param1:%d param2:%d",*param1, *param2);
        ret_val = EAT_TRUE;
    }
    return ret_val;
}

void eat_app_usb_eint(eat_bool en, EAT_USB_MODE mode)
{
    if(en)
    {
        eat_trace(" eat_app_usb_eint ENTRY--TEST+++++++++++++++++"); 
        eat_trace(" eat_app_usb_eint USB PLUG IN-mode=%d",mode); 
    }
    else
    {
        eat_trace(" eat_app_usb_eint ENTRY--TEST-------------------"); 
    
    }
}

// ************* Sonic functions set *************************

void sonic_ping(u8 ping_ms, u8 ping_pin, eat_bool imp_logic){
	unsigned int start = eat_get_current_time();
	/*
	// eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_LOW);  	// Switch ping_pin to LOW before the Trigger ping started
	eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_HIGH);			// Switch ping_pin to LOW before the Trigger ping started (negative impulse)
	while ( eat_get_duration_us(start) < ping_ms * 1000 ) { }		// Do nothing!!! 
	
	start = eat_get_current_time();*/
	
	
	// eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_HIGH);  // Switch ping_pin to HIGH
	eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_LOW);			// Switch ping_pin to HIGH (negative impulse)
	// eat_trace( " *** Ping start time is %d ***", start );
	while ( eat_get_duration_us(start) < ping_ms * 1000 ) { 		// Ping of ping_ms mS
		// Do nothing!!!
	}
	// eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_LOW);  // Switch ping_pin to LOW
	eat_gpio_write(EAT_PIN11_GPIO17, EAT_GPIO_LEVEL_HIGH);  // Switch ping_pin to LOW (negative imp.)
	// eat_trace( " *** Ping stop time is %d ***", eat_get_duration_us(start) );
};
/*
u32 sonic_distance(eat_bool unit_cm, u8 mgmt_window_ms, u8 echo_pin){
	u32 distance = 0;
	unsigned int echo_start = 0;
	
	echo_start = eat_get_current_time();
	// eat_trace( " *** Echo 1st start time is %d ***", echo_start );
	while (!eat_gpio_read(EAT_PIN13_GPIO19)) {								// Wait until Echo pin went to HIGH
						
		
		distance = eat_get_duration_us(echo_start);  // time to Echo front
		if ( distance > 50000) break;
	}
	eat_trace( " *** Echo waiting loop1 ended at %d /uS ***", distance);
	
	
	echo_start = eat_get_current_time();									// Reset the time counter
	// eat_trace( " *** Echo 2nd start time is %d ***", echo_start );
	
	while (eat_gpio_read(EAT_PIN13_GPIO19)) {								// Wait until Echo pin went to LOW
		
		
		distance = eat_get_duration_us(echo_start);  // distance in uS
		if ( distance > 100000) break;
	}
	
	eat_trace( " *** Echo duration is %d /uS ***", distance );
	distance /= 58; 														// distance in cm 
	eat_trace( " ****** Distance is %d cm *******", distance );
		
	return distance;
};
*/

u32 gpio_pulse_dur(eat_bool uS, eat_bool positive_impulse, EatPinName_enum input_pin_number, u32 max_waiting_time, u32 max_pulse_width, echo_pulse *sr04_echo_addr){
	eat_bool waiting_exceed = EAT_FALSE;
	eat_bool pulse_width_exceed = EAT_FALSE;
	unsigned int pulse_start = 0, pulse_end = 0;
	u32 pulse_width = 0;
	
	if( positive_impulse ) {
		
		pulse_start = eat_get_current_time();
		while (!eat_gpio_read(input_pin_number)) {							// Waiting for the pulse begin (fore front)
			pulse_end = eat_get_current_time();
			pulse_width = eat_get_duration_us(pulse_start);	
			if ( pulse_end - pulse_start >= max_waiting_time ) {
				waiting_exceed = EAT_TRUE;
				break;
			}
		}  
		sr04_echo_addr->waiting_time = pulse_width;
		
		pulse_start = eat_get_current_time();
		while (eat_gpio_read(input_pin_number) ) { 							// Waiting for the pulse end (rear front)
			pulse_end = eat_get_current_time();
			pulse_width = eat_get_duration_us(pulse_start);					// Pulse width in /uS
			if ( pulse_end - pulse_start >= max_pulse_width ) {
				pulse_width_exceed = EAT_TRUE;
				break;
			}
		}	
		sr04_echo_addr->width = pulse_width;
		
	} else {
		while (eat_gpio_read(input_pin_number)) {}  // Waiting for the pulse begin (fore front)
		pulse_start = eat_get_current_time();
		while (!eat_gpio_read(input_pin_number)) {}	// Waiting for the pulse end (rear front)
		pulse_end = eat_get_current_time();
		// pulse_width = eat_get_duration_us(pulse_start);
	}
	
	sr04_echo_addr->waiting_exceed = waiting_exceed;
	sr04_echo_addr->width_exceed = pulse_width_exceed;
	
	/* eat_trace( " **** SR04 response %d %d %s %s ****", sr04_echo_in->waiting_time, sr04_echo_in->width, 
	sr04_echo_in->waiting_exceed ? "Echo didn't come" : "Echo OK", sr04_echo_in->width_exceed ? "Distance out of range" : "Distance OK"); */
	
	if ( !uS ) pulse_width /= 1000;
	return pulse_width; // Returm the pulse width either in mS or in /uS
}

eat_bool sonic_sequence(u32 time_btw_pings_ms, u16 distance_min_cm, u16 distance_max_cm, echo_pulse *sr04_echo_addr){
	
	u32 distance = 0;
	eat_bool vehicle = EAT_FALSE; // Presence of a vehicle  
	
	/*
	sonic_ping(10, 11);     // (Ping duration mS, ping PIN)
	distance = sonic_distance(EAT_TRUE, 100, 13);  // First version of the distance evaluation 
	eat_trace( " **** SR04 response, 1st V sonic_distance %d ****", (distance) );
	*/
	
	
	sonic_ping(10, 11, EAT_FALSE);     // (Ping duration mS, ping PIN)
	distance = gpio_pulse_dur(EAT_TRUE, EAT_TRUE, EAT_PIN13_GPIO19, 10000, 36000, sr04_echo_addr); // Second version of the distance evaluation
	distance /= 58;
	// eat_trace( " **** SR04 response, gpio_pulse_dur distance  %d cm  ****", distance );
	
	if ( distance <= distance_min_cm ) vehicle = EAT_TRUE;
	else vehicle = EAT_FALSE;
	
	return vehicle;
};

void app_user1(void* data){	
	EatEvent_st event;	
	u32 event_num;
	
  	EatRtc_st* tdt = {0};						// Pointer to Time data recieved from the main process
	EatRtc_st rtc = {0};						// time data structure needed for the synchronization with server
	eat_bool rtc_result = EAT_FALSE;
	u16 h, m, s;								// hours, minutes, seconds - intermediate time data holders (issue with data type)
	
	unsigned char modem_buf[2048] = {0};		// Data buffer for the inf. exchange btw USER1 and core (modem)
	u16 len, l;
	u16 del_cmd = 500;							// Delay btw. GPRS SU commants
	u8 i;	
	
	eat_bool next_step = EAT_TRUE;
	eat_bool gprs_su_needed = EAT_TRUE;
	eat_bool http_su_needed = EAT_TRUE;
	eat_bool send_data	= EAT_FALSE;
	u8 step = 0;
	u8 data_str_result;
	char data_to_send[90] = "";
	const char *url = "http://kot60.online/sim800/sim800connect.php";	
	
	for (i = 0; i < 7; i++) eat_trace("111 User1 setup [%s]", gprs_su[i].atcmd);
	/*
	sprintf( (char *)modem_buf,"ATI\r\n");
	l = strlen( (const char *)modem_buf );						// Length of the message to be written to modem
    len = eat_modem_write(modem_buf, l);		// Length of data writen to the modem (core)
    if(len != l) eat_trace("Write to modem return len:%d", len); */
	
	sprintf( (char *)modem_buf,"AT+CCALR?\r\n");
	l = strlen( (const char *)modem_buf );						// Length of the message to be written to modem
    len = eat_modem_write(modem_buf, l);		// Length of data writen to the modem (core)
    if(len != l) eat_trace("Write to modem return len:%d", len);
	
	// eat_timer_start(EAT_TIMER_2, 1000);

	while(EAT_TRUE){
		// event_num = eat_get_event_num_for_user(EAT_USER_1);
		// eat_get_event_for_user(EAT_USER_1, &event);
		
		
		if (gprs_su_needed && next_step) {								// GPRS SetUp
							
			sprintf( (char *)modem_buf, gprs_su[step].atcmd);		// AT-command of GPRS SU to Modem buffer
				
			l = strlen( (const char *)modem_buf );					// Length of the message to be written to modem	
			len = eat_modem_write(modem_buf, l);					// Length of data writen to the modem (core). Sending cmd to the modem
			eat_trace("111 USER1 wrote to Modem: [%s], l = %d, len = %d ooooo ooooo", modem_buf, l, len);
			if(len != l) eat_trace("Write to modem return len:%d not equal to l:%d", len, l);
						
			eat_sleep(del_cmd);
			
			next_step = EAT_FALSE;
			
			step++;
			if (step == 4) step = 6;  								// Block USR and PWD setting
			if (step == 6) del_cmd = 2000;
			if (step == 7) {
				step = 0;
				gprs_su_needed = EAT_FALSE;    						// End of the GPRS SetUp				
			}
			eat_trace("111 USER1 status: step [%d], gprs_su_needed [%d]", step, gprs_su_needed);
		}
		
		if (send_data && next_step) { 								// Free to send data to the server	(HTTP session)		
			rtc_result = eat_get_rtc(&rtc);
			
			if ( step == 2 ) {											// Data preparation to be written to the modem
				data_str_result = sprintf ((char *)modem_buf, "AT+HTTPPARA=\"URL\",\"%s?H=%d&M=%d&S=%d&Ht=%d&Mt=%d&St=%d\"\r\n\r\n", url, rtc.hour, rtc.min, rtc.sec, tdt->hour, tdt->min, tdt->sec);				
			} else {
				sprintf( (char *)modem_buf, http_su[step].atcmd);		// AT-command of HTTP SU to Modem buffer
			}
			
			if ( step == 4 ) { 
				eat_sleep( 5000 );										// Delay 3.0S/10mS after execution of HTTPREAD				
			}
			eat_trace ("111 USER1 Timestamp: %d:%d:%d (Write to modem)", rtc.hour, rtc.min, rtc.sec);
			
			l = strlen( (const char *)modem_buf );					// Length of the message to be written to modem	
			len = eat_modem_write(modem_buf, l);					// Length of data writen to the modem (core) & writing data to the modem
			eat_trace("111 USER1 to Modem: [%s], l = %d, len = %d", modem_buf, l, len);
			if(len != l) eat_trace("Write to modem return len:%d not equal to l:%d", len, l);
			
			next_step = EAT_FALSE;
			
			step++;			
			if (step == 6) {										// All HTTP command have been fulfilled (w/o httpstatus)
				step = 0;
				send_data = EAT_FALSE;
			}
		}
		
		event_num = eat_get_event_num_for_user(EAT_USER_1);
		eat_get_event_for_user(EAT_USER_1, &event);
		
		switch(event.event)
        {
			/* case EAT_EVENT_TIMER:
				{
					if ( event.data.timer.timer_id == EAT_TIMER_2 ) {		
						eat_timer_stop(EAT_TIMER_2);		
						eat_timer_start(EAT_TIMER_2, 1000);						
						eat_trace("111 USER1 Timer_2");
					}	
				}
				break; */
				
            case EAT_EVENT_MDM_READY_RD:  							// modem answer
                {
                    len = eat_modem_read(modem_buf, 2048);  		// Reading data from the modem
					rtc_result = eat_get_rtc(&rtc);
					eat_trace ("111 USER1 Timestamp: %d:%d:%d (Read from modem)", rtc.hour, rtc.min, rtc.sec);
			
                    if(len != 0)
                    {
                        modem_buf[len] = '\0';
						next_step = EAT_TRUE;
                        eat_trace("111 USER1 from modem: [%s], next_step: [%d]", modem_buf, next_step);
						
						if ( strstr(modem_buf, "Time:") ) {
							sscanf(strstr( (const char *) modem_buf, "Time:") + 5, "%d:%d:%d", &h, &m, &s); 		//&rtc.hour, &rtc.min, &rtc.sec);
							eat_trace ("111 USER1 Server Time: %d:%d:%d (Read from modem)", h, m, s); 				//rtc.hour, rtc.min, rtc.sec);
							
							if ( h != rtc.hour || (m - rtc.min) > 2 || (m - rtc.min) < -2 ) {
								rtc.hour = h; rtc.min = m; rtc.sec = s;												// changed 11.11
								rtc_result = eat_set_rtc(&rtc);
								eat_trace ("111 USER1 sim800 RTC synchronized with server");
							}
						}
						
                    } else  eat_trace("Read modem fail!");
                }
                break;
            case EAT_EVENT_MDM_READY_WR:   // write to the modem
                {
                    eat_trace("event :%s,can continue to write to modem", "EAT_EVENT_MDM_READY_WR");
                }
                break;
            case EAT_EVENT_MDM_RI:
                {
                    eat_trace("Ring level", event.data.mdm_ri);
                }
                break;
			case EAT_EVENT_USER_MSG:     // get massage from the 'main' process
				{					
					// *** Reading of the message from other processes (i.g. Main) ***
					eat_trace("111 USER1 event.data.user_msg: %d %d %d %s %x", event.data.user_msg.src, event.data.user_msg.use_point, 
					event.data.user_msg.len, event.data.user_msg.data, event.data.user_msg.data_p);  
					
					if (event.data.user_msg.use_point) {					// If the message from main uses pointer
						tdt = (EatRtc_st *) &event.data.user_msg.data_p;  	// no error. A pointer to the RTC structure copied ti the USER1 tdt pointer variable
						
						show_time ( (EatRtc_st*) &event.data.user_msg.data_p, "msg.data_p" ); 	// Display the whole time structure
						show_time ( (EatRtc_st*) tdt, "tdt" ); 									// trial. Should be the same as previous
						
						eat_trace("111 User 1: tdt = %x &tdt = %x, data.p = %x &data.p = %x, (EatRtc_st*) &data_p = %x", tdt, &tdt, event.data.user_msg.data_p, &event.data.user_msg.data_p, (EatRtc_st*) &event.data.user_msg.data_p);
						eat_trace("111 USER 1: Time:  %d:%d:%d  Date: %d/%d/%d   Week day = %d", tdt->hour, tdt->min, tdt->sec, tdt->day, tdt->mon, tdt->year, tdt->wday ); 
						show_rtc_mem ( (char*) tdt);	

						send_data = EAT_TRUE;
						next_step = EAT_TRUE; step = 0;
					}
				}
				break; 
			default:
                break;
		}
	}	
}

void atcmd_to_modem(char* atcmd, EatEvent_st* event) {
	unsigned char modem_buf[2048] = {0};		// Data buffer for the inf. exchange btw USER1 and core (modem)
	u16 len, l;
	u16 tick = 0;
	sprintf( (char *)modem_buf, atcmd);		// Some AT-command
	l = strlen( (const char *)modem_buf );		// Length of the message to be written to modem
	
	len = eat_modem_write(modem_buf, l);		// Length of data writen to the modem (core)
	// eat_trace(" ooooo oooooo Modem write: l = %d, len = %d ooooo ooooo", l, len);
	if(len != l) eat_trace("Write to modem return len:%d not equal to l:%d", len, l);
	
	eat_sleep(500);
	
	while(event->event != EAT_EVENT_MDM_READY_RD) tick++;
	
	len = eat_modem_read(modem_buf, 2048);
    if(len != 0)
    {
        modem_buf[len] = '\0';
        eat_trace("Read from modem atcmd_to_modem  : %s", modem_buf);
    } else  eat_trace("Read modem fail!");
}

void show_time (EatRtc_st* time, char *msg) {					// Shows the content of the EatRtc_st structure
	eat_trace("FFF Function SHOW_TIME %s Time(h:m:S): %d:%d:%d Date(d/m/Y/dow): %d/%d/%d %d", msg, time->hour, time->min, time->sec, time->day, time->mon, time->year, time->wday);
}

void show_rtc_mem (char* time) {
	char mem[14] = {0};
	
	u8 i;
	
	for( i = 0; i < 14; i++) {
		mem[i] += *(time + i);
	}
	
	eat_trace ("FFF Function SHOW_RTC_MEM:  mem = %d:%d:%d %d/%d/%d/%d %d%d%d%d %d%d%d", mem[0] ,mem[1], mem[2], mem[3], mem[4], mem[5] ,mem[6], mem[7], mem[8], mem[9], mem[10] ,mem[11], mem[12], mem[13] );
}

void app_main(void *data)
{
    EatEvent_st event;
    u8 buf[2048];
	char sbuf[25];
    u16 len = 0;
	
	char* result_vehicle = {"Vehicle absent "};
	
	echo_pulse sr04_echo = {0};  							// Declaration of the pulse parameters structure
	
	static u8 sonic_positive_number = 0;					// Positive (Car present, Techo < Tmin) responses number
	const u8 max_conf_num = 5;								// Max pos.resp.number, needed for the confirmation
	
	eat_bool sonic_positive = EAT_FALSE;					// Result of each sonic distance measurement
															// Confirmation after some (5-10) positive detections, reset after 5-10 negative answers
	eat_bool report_done = EAT_FALSE;						// Car presence confirmed and the report is sent
	
	unsigned int time1 = eat_get_current_time();  			// Saves the current time
	eat_bool rtc_result = EAT_FALSE;						// Result of the RTC setup
	EatRtc_st rtc = {0};
	
	eat_trace("000 APP_MAIN (setup) line 519: rtc =   %x &rtc =   %x, (const unsigned char **) &rtc =   %x", rtc, &rtc, (const unsigned char **) &rtc );
	show_time ( &rtc, "rtc" );
	
	rtc.year = 20;	rtc.mon = 9;	rtc.day = 7;	rtc.wday = 2;
    rtc.hour = 12;	rtc.min = 06;	rtc.sec = 45;
    rtc_result = eat_set_rtc(&rtc);							// RTC setup procedure
	show_time ( &rtc, "rtc" );
	
	eat_trace("000 APP_MAIN (setup) line 533: rtc =   %x &rtc =   %x, (const unsigned char **) &rtc =   %x", rtc, &rtc, (const unsigned char **) &rtc );
		
	if ( rtc_result ) eat_trace (" **** RTC set up OK ****"); else eat_trace (" **** RTC malfunction! ****");
	
    APP_InitRegions();	//Init app RAM, first step
    APP_init_clib(); 	//C library initialize, second step
	
	// SetUp pins of the interface with the ultrasonic sensor. Trigger and Echo signal lines
    // eat_gpio_setup(EAT_PIN11_GPIO17, EAT_GPIO_DIR_OUTPUT, EAT_GPIO_LEVEL_LOW);		// EVB: SPI_CLK connector C6 -> output Trig
	eat_gpio_setup(EAT_PIN11_GPIO17, EAT_GPIO_DIR_OUTPUT, EAT_GPIO_LEVEL_HIGH);			// EVB: SPI_CLK connector C6 -> output Trig (negative logic)
	eat_gpio_setup(EAT_PIN13_GPIO19, EAT_GPIO_DIR_INPUT, EAT_GPIO_LEVEL_LOW);			// EVB: SPI_MOSI connector C3 -> input Echo
	
	eat_timer_start(EAT_TIMER_1, 100);
	
    while(EAT_TRUE)
    {
        eat_get_event(&event);															// Does it wait for an event???
	    // eat_trace("MSG id %x", event.event);
		
		sonic_positive = sonic_sequence(100, 150, 450, &sr04_echo);
		rtc_result = eat_get_rtc(&rtc);		// Just for testing 
				
		if ( sonic_positive && sonic_positive_number < max_conf_num ) {
			sonic_positive_number++;													// Count of positive detections till the maximum number is reached
			if ( sonic_positive_number == max_conf_num ) {								// The sonic positive signals numer reached its maximum level, vehicle presence confirmed
						
				result_vehicle = "Vehicle present";
				eat_kpled_sw(EAT_TRUE);													// Switch ON the LED on the sim800 module
						
				if ( !report_done ) {								
																						// request the timestamp from the server, get RTC timestamp, sent the event information to the server
					rtc_result = eat_get_rtc(&rtc);		

					eat_trace("000 APP_MAIN (loop) line 569: rtc =   %x &rtc =   %x, (const unsigned char **) &rtc =   %x", rtc, &rtc, (const unsigned char **) &rtc );
					eat_trace ("000 APP_MAIN (loop) line:572  Vehicle detected,  Time:  %d:%d:%d  Date: %d/%d/%d", rtc.hour,rtc.min,rtc.sec, rtc.day,rtc.mon,rtc.year );

					// (from task main, to task user_1, use_point, message data length 4 bytes addr, Message data body parts (EAT_NULL - not used), Message data section)
					eat_trace ("000 APP_MAIN (loop) line:577 time data to send to USER1: %x\n", (const unsigned char **) &rtc );
					eat_send_msg_to_user(EAT_USER_0, EAT_USER_1, EAT_TRUE, 4, EAT_NULL, (const unsigned char **) &rtc); 
							
					show_rtc_mem ( (char *) &rtc );

					show_time ( &rtc, "rtc" );
					
					report_done = EAT_TRUE;						
				}					
			}				
				
		} else if ( !sonic_positive && sonic_positive_number > 0 ) {
			sonic_positive_number--;									// Count the negative responses untill "0" is reached
			if ( sonic_positive_number == 0 ) {							// The sonic negative results reached zero, vehicle left the detection area
						
				report_done = EAT_FALSE;								// Ready for the next detection and report	
				result_vehicle = "Vehicle absent ";
				eat_kpled_sw(EAT_FALSE);
			}				
		}
		
		// *******  Timestemp, distance, US-signal and measurement result *******
		/* eat_trace (" ********  Time: %d:%d:%d Date: %d/%d/%d  Dist.: %d cm -> %s,  %s ********", rtc.hour, rtc.min, rtc.sec, rtc.day, rtc.mon, rtc.year,  
		sr04_echo.width/58, sonic_positive ? "US-signal HIGH":"US-signal LOW", result_vehicle ); */
				
		
        switch(event.event)
        {
			
			case EAT_EVENT_TIMER:
				{
					if ( event.data.timer.timer_id == EAT_TIMER_1 ) {							  
						eat_timer_start(EAT_TIMER_1, 80);								
					}	
				}
				break;
			
			
            case EAT_EVENT_MDM_READY_RD:
                {
                    u8 param1,param2;
                    len = 0;
                    len = eat_modem_read(buf, 2048);  // Read data from the modem (??)
					strncpy(sbuf, (const char *)buf, 24);
					sbuf[24] = '\0';
                    if(len > 0)
                    {
						// eat_trace(" 1. From Mdm buffer: %s",sbuf);
                        // Get the testing parameter
                        if( eat_modem_data_parse(buf,len,&param1,&param2) )  // is it the response for AT+CEAT ?
                        {
                            // Entry gpio test module
                            // eat_module_test_gpio(param1, param2); 
							
							eat_trace(" ********  EAT_EVENT_MDM_READY_RD %s Param1 %d, Param2 %d *******", sbuf, param1, param2);
                        }
                        else
                        {
                            eat_trace("From Mdm buffer: %s",buf);
                        }
                    }
                }
                break; 
            case EAT_EVENT_MDM_READY_WR:  
            case EAT_EVENT_UART_READY_RD:
                break;
            case EAT_EVENT_UART_SEND_COMPLETE :
                break;
            default:
                break;
        }

    }

}


