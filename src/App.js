import {useState} from 'react';
import { makeStyles } from '@material-ui/core/styles';
import { Button, Typography, Container, CircularProgress} from '@material-ui/core';
import Stepper from './component/Stepper';
import FirstStep from './component/FirstStep';
import SecondStep from './component/SecondStep';
import ThirdStep from './component/ThirdStep';
import FinalStep from './component/FinalStep';
import Context from './store/store';
import axios from 'axios';
import { __ } from '@wordpress/i18n';
import qs from 'qs';
const useStyles = makeStyles((theme) => ({
    instructions: {
        marginTop: theme.spacing(1),
        marginBottom: theme.spacing(1),
    },
}));
function App(props) {
    const [loading, setLoading] = useState(false);
    const [state, setState] = useState({
        firstName: '',
        email: '',
        desc:'',
        licenseCode: '',
        age: '',
        FirstCheckbox:false,
        checkedA: false,
        checkedB: false,
        consetCheck:'yes',
    });
    
    const classes = useStyles();
    const [activeStep, setActiveStep] = useState(0);
    const steps = [ __( 'General Settings', 'track-orders-for-woocommerce' ), __( 'Industry', 'track-orders-for-woocommerce' ), __( 'Accept consent', 'track-orders-for-woocommerce' ), __( 'Final Step', 'track-orders-for-woocommerce' )];

    
    const onFormFieldHandler = (event) => {
        let value = ('checkbox' === event.target.type ) ? event.target.checked : event.target.value;
        setState({ ...state, [event.target.name]: value });
    };
    const getStepContent = (stepIndex) => {
        switch (stepIndex) {
            case 0:
                return (<FirstStep />);
            case 1:
                return (<SecondStep/>);
            case 2:
                return <ThirdStep />;
            case 3:
            return <FinalStep />;
            case 4:
                return <h1>{__( 'Thanks for your details', 'track-orders-for-woocommerce' )}</h1>;
            default:
                return __( 'Unknown stepIndex', 'track-orders-for-woocommerce' );
        }
    }
    const handleNext = () => {
        setActiveStep((prevActiveStep) => prevActiveStep + 1);
    };

    const handleBack = () => {
        setActiveStep((prevActiveStep) => prevActiveStep - 1);
    };

    const handleFormSubmit = (e) => {
        e.preventDefault();
        setLoading(true);
        const user = {
            ...state,
            'action': 'tofw_wps_standard_save_settings_filter',
            nonce: frontend_ajax_object.wps_standard_nonce,   // pass the nonce here
        };
        
        axios.post(frontend_ajax_object.ajaxurl, qs.stringify(user) )
            .then(res => {
                setLoading(false);
                console.log( res.data);
                handleNext();
                setTimeout(() => {
                  window.location.href = frontend_ajax_object.redirect_url; 
                    return null;
                }, 3000);
            }).catch(error=>{
                console.log(error);
        })
        
    }

    let nextButton = (
        <Button
            variant="contained" color="primary" onClick={handleNext} size="large">
            Next
        </Button>
    );
    if (activeStep === steps.length-1 ) {
        nextButton = (
            <Button
                onClick={handleFormSubmit}
                variant="contained" color="primary" size="large">
                Finish
            </Button>
        )
    } 
    return (
        <Context.Provider value={{
            formFields:state,
            changeHandler:  onFormFieldHandler,  
        }}>
            <div className="wpsMsfWrapper">
                <Stepper activeStep={activeStep} steps={steps}/>
                <div className="wpsHeadingWrap">
                    <h2>{__( 'Welcome to WPSwings', 'track-orders-for-woocommerce' ) }</h2>
                    <p>{__('Complete The steps to get started','track-orders-for-woocommerce') }</p>
                </div>
                <Container maxWidth="sm">
                    <form className="wpsMsf">
                        <Typography className={classes.instructions}>
                            {(loading) ? <CircularProgress className="wpsCircularProgress" /> :getStepContent(activeStep)}
                        </Typography>
                        <div className="wpsButtonWrap">
                            {activeStep !== steps.length && <Button
                                disabled={activeStep === 0}
                                onClick={handleBack}
                                variant="contained" size="large">
                            Back
                            </Button>}
                            {activeStep !== steps.length && nextButton}
                        </div>
                    </form>
                </Container >
            </div>
        </Context.Provider>
    );
}

export default App;